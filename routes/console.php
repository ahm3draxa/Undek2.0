<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled work
|--------------------------------------------------------------------------
|
| Shared hosting has no always-on queue worker, so one cron entry drives
| everything:  * * * * *  php /path/to/artisan schedule:run
|
| Each run is a MySQL connection, and the host caps connections per hour,
| so these intervals are deliberately unhurried.
|
*/

// Queued mail (marketing campaigns, invitations): one batch every five
// minutes — 12 connections an hour — sized so the per-minute sending rate
// stays under the mail host's limit. Never overlaps itself.
Schedule::command('queue:work', [
    '--stop-when-empty',
    '--max-jobs' => config('mail.marketing.per_minute') * 5,
    '--max-time' => 240,
    '--tries' => 3,
])->everyFiveMinutes()->withoutOverlapping(10);

// Shared inboxes (info@ / support@) — 6 connections an hour; "Check now" in
// the inbox fetches on demand.
Schedule::command('inbox:fetch')->everyTenMinutes()->withoutOverlapping(15);
