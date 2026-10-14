<?php

namespace App\Console\Commands;

use App\Services\Inbox\InboxFetcher;
use Illuminate\Console\Command;
use Throwable;

class FetchInbox extends Command
{
    protected $signature = 'inbox:fetch {mailbox? : One mailbox key from config/inbox.php; all configured ones if omitted}';

    protected $description = 'Import new mail from the shared company mailboxes into the portal inbox';

    public function handle(InboxFetcher $fetcher): int
    {
        $mailboxes = $this->argument('mailbox')
            ? [$this->argument('mailbox')]
            : array_keys(config('inbox.mailboxes'));

        $failed = false;

        foreach ($mailboxes as $mailbox) {
            if (! $fetcher->isConfigured($mailbox)) {
                $this->line("$mailbox: skipped (no password configured)");

                continue;
            }

            try {
                $this->info("$mailbox: {$fetcher->fetch($mailbox)} new message(s)");
            } catch (Throwable $e) {
                $failed = true;
                report($e);
                $this->error("$mailbox: {$e->getMessage()}");
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
