<?php

/*
|--------------------------------------------------------------------------
| Shared inboxes
|--------------------------------------------------------------------------
|
| Company mailboxes pulled into the portal over IMAP, and replied to over
| SMTP from the same account. Defaults are Hostinger's mail servers. A
| mailbox without a password set is simply skipped.
|
| Mail stays on the mail server untouched (fetched in "peek" mode, so it
| isn't even marked read there) — the portal keeps its own copy.
|
*/

return [

    'mailboxes' => [
        'info' => [
            'address' => env('INBOX_INFO_ADDRESS', 'info@undek.com'),
            'password' => env('INBOX_INFO_PASSWORD'),
        ],
        'support' => [
            'address' => env('INBOX_SUPPORT_ADDRESS', 'support@undek.com'),
            'password' => env('INBOX_SUPPORT_PASSWORD'),
        ],
    ],

    'imap' => [
        'host' => env('INBOX_IMAP_HOST', 'imap.hostinger.com'),
        'port' => (int) env('INBOX_IMAP_PORT', 993),
        'encryption' => env('INBOX_IMAP_ENCRYPTION', 'ssl'),
        'folder' => 'INBOX',
    ],

    'smtp' => [
        'host' => env('INBOX_SMTP_HOST', 'smtp.hostinger.com'),
        'port' => (int) env('INBOX_SMTP_PORT', 465),
    ],

    // The very first fetch only imports this many recent messages rather
    // than the mailbox's entire history.
    'first_sync_count' => 50,

    // Upper bound per mailbox per run; anything beyond is picked up next run.
    'max_per_run' => 40,

    // Attachments bigger than this are listed but not downloaded.
    'attachment_max_kb' => 15 * 1024,

    // Extensions never stored, whatever they claim to be — executables and
    // scripts that only exist in email to infect whoever opens them.
    'blocked_extensions' => [
        'exe', 'msi', 'bat', 'cmd', 'com', 'scr', 'pif', 'cpl', 'dll', 'sys',
        'js', 'jse', 'vbs', 'vbe', 'wsf', 'wsh', 'ps1', 'psm1', 'hta', 'lnk',
        'jar', 'apk', 'app', 'dmg', 'iso', 'img', 'reg', 'scf', 'msc', 'gadget',
        'html', 'htm', 'svg', 'php', 'phtml', 'sh',
    ],

];
