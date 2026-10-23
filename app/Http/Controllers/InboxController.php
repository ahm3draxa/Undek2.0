<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\StreamsStoredFiles;
use App\Mail\InboxReplyMail;
use App\Models\InboxAttachment;
use App\Models\InboxMessage;
use App\Services\Inbox\InboxFetcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class InboxController extends Controller
{
    use StreamsStoredFiles;

    public function index(Request $request, InboxFetcher $fetcher): View
    {
        $mailboxes = InboxMessage::mailboxesFor($request->user());
        $mailbox = in_array($request->query('mailbox'), $mailboxes, true) ? $request->query('mailbox') : null;
        $unreadOnly = $request->boolean('unread');
        $search = trim((string) $request->query('q'));

        $messages = InboxMessage::incoming()
            ->whereIn('mailbox', $mailboxes)
            ->withCount('attachments')
            ->when($mailbox, fn ($q) => $q->where('mailbox', $mailbox))
            ->when($unreadOnly, fn ($q) => $q->whereNull('read_at'))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('subject', 'like', "%$search%")
                ->orWhere('from_email', 'like', "%$search%")
                ->orWhere('from_name', 'like', "%$search%")))
            ->latest('received_at')
            ->paginate(25)
            ->withQueryString();

        $unreadCounts = InboxMessage::unread()->whereIn('mailbox', $mailboxes)->selectRaw('mailbox, count(*) as total')->groupBy('mailbox')->pluck('total', 'mailbox');
        $configured = collect($mailboxes)->mapWithKeys(fn ($m) => [$m => $fetcher->isConfigured($m)]);

        return view('inbox.index', compact('messages', 'mailboxes', 'mailbox', 'unreadOnly', 'search', 'unreadCounts', 'configured'));
    }

    public function show(Request $request, InboxMessage $message): View
    {
        $this->authorizeMailbox($message);

        if ($message->isUnread()) {
            $message->update(['read_at' => now(), 'read_by' => $request->user()->id]);
        }

        $message->load(['attachments', 'replies.sender', 'reader']);

        return view('inbox.show', compact('message'));
    }

    /**
     * The email's HTML, served on its own for a sandboxed iframe. Email HTML is
     * untrusted: the CSP header blocks all script, forms and remote content,
     * and the sandbox gives it an opaque origin with no access to the portal.
     * Remote images (often tracking pixels) load only when explicitly asked.
     */
    public function body(Request $request, InboxMessage $message): Response
    {
        $this->authorizeMailbox($message);

        $images = $request->boolean('images') ? 'data: https:' : 'data:';

        $html = '<!doctype html><html><head><meta charset="utf-8"><base target="_blank">'
            .'<style>body{font-family:Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;color:#1f2937;margin:16px;word-wrap:break-word}img{max-width:100%;height:auto}</style>'
            .'</head><body>'
            .($message->body_html ?: '<pre style="white-space:pre-wrap;font-family:inherit">'.e((string) $message->body_text).'</pre>')
            .'</body></html>';

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; img-src $images; font-src data:; sandbox allow-popups allow-popups-to-escape-sandbox",
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function reply(Request $request, InboxMessage $message): RedirectResponse
    {
        $this->authorizeMailbox($message);
        abort_unless($message->direction === 'in' && $message->from_email, 422, 'This message has no sender to reply to.');

        $data = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
        ]);

        $settings = config("inbox.mailboxes.{$message->mailbox}");
        abort_unless(filled($settings['password'] ?? null), 422, 'This mailbox is not configured for sending.');

        $subject = Str::startsWith(Str::lower((string) $message->subject), 're:') ? $message->subject : 'Re: '.$message->subject;
        $messageId = Str::uuid().'@'.Str::after($settings['address'], '@');

        // Sent through the mailbox's own SMTP account, so it genuinely comes
        // from info@/support@ and passes SPF/DKIM like any webmail reply.
        $mailer = Mail::build([
            'transport' => 'smtp',
            'scheme' => 'smtps',
            'host' => config('inbox.smtp.host'),
            'port' => config('inbox.smtp.port'),
            'username' => $settings['address'],
            'password' => $settings['password'],
            'timeout' => 30,
        ]);

        try {
            $mailer->to($message->from_email, $message->from_name)
                ->send(new InboxReplyMail($message, $subject, $data['body'], $messageId));
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['body' => 'The reply could not be sent: '.$e->getMessage()]);
        }

        $message->replies()->create([
            'mailbox' => $message->mailbox,
            'direction' => 'out',
            'message_id' => "<$messageId>",
            'in_reply_to' => $message->message_id,
            'from_name' => $request->user()->name,
            'from_email' => $settings['address'],
            'to' => $message->from_email,
            'subject' => $subject,
            'body_text' => $data['body'],
            'received_at' => now(),
            'sent_by' => $request->user()->id,
        ]);

        return redirect()->route('inbox.show', $message)->with('status', "Reply sent to {$message->from_email}.");
    }

    public function markUnread(InboxMessage $message): RedirectResponse
    {
        $this->authorizeMailbox($message);

        $message->update(['read_at' => null, 'read_by' => null]);

        return redirect()->route('inbox.index', ['mailbox' => $message->mailbox])->with('status', 'Marked as unread.');
    }

    public function attachment(InboxMessage $message, InboxAttachment $attachment): StreamedResponse
    {
        $this->authorizeMailbox($message);
        abort_unless($attachment->inbox_message_id === $message->id && $attachment->isStored(), 404);

        return $this->streamStoredFile($attachment->disk, $attachment->path, $attachment->original_name, $attachment->mime_type);
    }

    /**
     * "Check now" — the scheduler already fetches every few minutes; this is
     * for when someone is waiting on a specific email. Rate-limited so it
     * can't be used to hammer the mail server.
     */
    public function refresh(Request $request, InboxFetcher $fetcher): RedirectResponse
    {
        if (RateLimiter::tooManyAttempts('inbox-refresh', 1)) {
            return back()->with('status', 'Checked moments ago — try again in '.RateLimiter::availableIn('inbox-refresh').' seconds.');
        }

        RateLimiter::hit('inbox-refresh', 60);

        $results = [];
        foreach (InboxMessage::mailboxesFor($request->user()) as $mailbox) {
            if (! $fetcher->isConfigured($mailbox)) {
                continue;
            }

            try {
                $results[] = "$mailbox: {$fetcher->fetch($mailbox)} new";
            } catch (Throwable $e) {
                report($e);
                $results[] = "$mailbox: failed ({$e->getMessage()})";
            }
        }

        return back()->with('status', $results ? implode(' · ', $results) : 'No mailboxes are configured yet.');
    }

    /**
     * Each mailbox belongs to a team — info@ to Management, support@ to IT
     * Support — so access is checked per message, not just per page.
     */
    private function authorizeMailbox(InboxMessage $message): void
    {
        abort_unless(in_array($message->mailbox, InboxMessage::mailboxesFor(auth()->user()), true), 403);
    }
}
