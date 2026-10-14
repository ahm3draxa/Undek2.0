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

// Queued mail (marketing campaigns): a small batch every two minutes keeps
// sending under the mail host's hourly limit and never overlaps itself.
Schedule::command('queue:work', [
    '--stop-when-empty',
    '--max-jobs' => config('mail.marketing.per_minute') * 2,
    '--max-time' => 100,
    '--tries' => 3,
])->everyTwoMinutes()->withoutOverlapping(10);

// Shared inboxes (info@ / support@).
Schedule::command('inbox:fetch')->everyFiveMinutes()->withoutOverlapping(10);
