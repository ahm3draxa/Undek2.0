<?php

namespace App\Services\Inbox;

use App\Models\InboxMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

/**
 * Pulls new mail from a shared mailbox into the portal. Incremental by IMAP
 * UID: each run only fetches messages newer than the last one stored, and
 * never changes anything on the mail server.
 */
class InboxFetcher
{
    /** Body text beyond this is cut off — protects the database from huge newsletters. */
    private const MAX_BODY_BYTES = 2 * 1024 * 1024;

    public function isConfigured(string $mailbox): bool
    {
        return filled(config("inbox.mailboxes.$mailbox.password"));
    }

    /**
     * @return int how many new messages were stored
     */
    public function fetch(string $mailbox): int
    {
        $settings = config("inbox.mailboxes.$mailbox") ?? throw new RuntimeException("Unknown mailbox [$mailbox].");

        if (! $this->isConfigured($mailbox)) {
            throw new RuntimeException("Mailbox [$mailbox] has no password set (INBOX_".strtoupper($mailbox).'_PASSWORD).');
        }

        $client = (new ClientManager)->make([
            'host' => config('inbox.imap.host'),
            'port' => config('inbox.imap.port'),
            'encryption' => config('inbox.imap.encryption'),
            'validate_cert' => true,
            'username' => $settings['address'],
            'password' => $settings['password'],
            'protocol' => 'imap',
            'timeout' => 30,
        ]);

        $client->connect();

        try {
            $query = $client->getFolderByPath(config('inbox.imap.folder'))->query()->leaveUnread();

            $lastUid = (int) InboxMessage::where('mailbox', $mailbox)->whereNotNull('uid')->max('uid');

            $uids = collect($client->getConnection()->getUid()->validatedData())
                ->map(fn ($uid) => (int) $uid)
                ->filter(fn (int $uid) => $uid > $lastUid)
                ->sort()
                ->values();

            if ($lastUid === 0) {
                $uids = $uids->slice(-config('inbox.first_sync_count'))->values();
            }

            $stored = 0;

            foreach ($uids->take(config('inbox.max_per_run')) as $uid) {
                try {
                    $this->store($mailbox, $uid, $query->getMessageByUid($uid));
                } catch (Throwable $e) {
                    // Record a placeholder so one malformed message can't block
                    // every message after it on every future run.
                    Log::warning("Inbox [$mailbox]: could not import UID $uid", ['error' => $e->getMessage()]);
                    InboxMessage::firstOrCreate(
                        ['mailbox' => $mailbox, 'uid' => $uid],
                        ['subject' => '(This message could not be imported — open it in webmail)', 'received_at' => now()],
                    );
                }

                $stored++;
            }

            return $stored;
        } finally {
            $client->disconnect();
        }
    }

    private function store(string $mailbox, int $uid, Message $message): void
    {
        $from = $message->getFrom()->first();

        DB::transaction(function () use ($mailbox, $uid, $message, $from) {
            $record = InboxMessage::firstOrCreate(['mailbox' => $mailbox, 'uid' => $uid], [
                'direction' => 'in',
                'message_id' => Str::limit((string) $message->getMessageId(), 250, ''),
                'in_reply_to' => Str::limit((string) $message->getInReplyTo(), 250, ''),
                'from_name' => Str::limit((string) ($from->personal ?? ''), 250, '') ?: null,
                'from_email' => Str::limit((string) ($from->mail ?? ''), 250, '') ?: null,
                'to' => $this->addresses($message->getTo()->all()),
                'cc' => $this->addresses($message->getCc()->all()),
                'subject' => Str::limit((string) $message->getSubject(), 250, '') ?: '(no subject)',
                'body_text' => $message->hasTextBody() ? $this->cap($message->getTextBody()) : null,
                'body_html' => $message->hasHTMLBody() ? $this->cap($message->getHTMLBody()) : null,
                'received_at' => $this->date($message),
            ]);

            if (! $record->wasRecentlyCreated) {
                return;
            }

            foreach ($message->getAttachments() as $attachment) {
                $this->storeAttachment($record, $attachment);
            }
        });
    }

    /**
     * Attachments are kept as inert private files and only ever offered as
     * downloads. Executable/script types aren't stored at all, and oversized
     * files are listed but not kept.
     */
    private function storeAttachment(InboxMessage $record, $attachment): void
    {
        $name = Str::limit(preg_replace('/[\x00-\x1F\x7F\/\\\\]/u', '', (string) ($attachment->getName() ?: 'attachment')), 200, '');
        $content = (string) $attachment->getContent();
        $size = strlen($content);
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        $blocked = in_array($extension, config('inbox.blocked_extensions'), true);
        $tooBig = $size > config('inbox.attachment_max_kb') * 1024;

        if ($blocked || $tooBig || $size === 0) {
            $record->attachments()->create(['original_name' => $name, 'size' => $size, 'mime_type' => $blocked ? 'blocked' : null]);

            return;
        }

        // Only allowlisted types whose real content matches keep their type
        // (and so may be previewed); anything else downloads as raw bytes.
        $sniffed = (new \finfo(FILEINFO_MIME_TYPE))->buffer($content);
        $allowedMimes = config("uploads.types.$extension.mimes", []);
        $mime = in_array($sniffed, $allowedMimes, true) ? $allowedMimes[0] : 'application/octet-stream';

        $path = 'inbox/'.now()->format('Y/m').'/'.Str::uuid().($extension && $mime !== 'application/octet-stream' ? ".$extension" : '');
        Storage::disk('local')->put($path, $content);

        $record->attachments()->create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => $name,
            'mime_type' => $mime,
            'size' => $size,
        ]);
    }

    private function addresses(array $addresses): ?string
    {
        $list = collect($addresses)->map(fn ($a) => $a->mail ?? null)->filter()->implode(', ');

        return $list !== '' ? Str::limit($list, 2000, '') : null;
    }

    private function date(Message $message): Carbon
    {
        try {
            $date = $message->getDate()->first();

            return $date ? Carbon::instance($date) : now();
        } catch (Throwable) {
            return now();
        }
    }

    private function cap(string $body): string
    {
        return strlen($body) > self::MAX_BODY_BYTES ? mb_strcut($body, 0, self::MAX_BODY_BYTES) : $body;
    }
}
