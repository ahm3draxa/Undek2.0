<?php

/*
|--------------------------------------------------------------------------
| Upload safety rules
|--------------------------------------------------------------------------
|
| Every uploaded file is checked by App\Services\Uploads\SecureUploader:
| the extension must be on a profile's allowlist AND the file's real content
| (sniffed from its bytes, not the browser-supplied type) must match that
| extension. Files are stored privately under random names and only ever
| served through an authorized download route — never executed, never
| unzipped, never reachable by a guessable public URL.
|
| Sizes are in kilobytes. PHP's own upload_max_filesize / post_max_size on
| the server must be at least as large as the biggest limit here, or PHP
| rejects the upload before Laravel ever sees it.
|
*/

return [

    // Each extension maps to the content types its bytes may sniff as. Office
    // formats are ZIP containers underneath, so some fileinfo builds report
    // them as plain application/zip.
    'types' => [
        'jpg' => ['kind' => 'image', 'mimes' => ['image/jpeg']],
        'jpeg' => ['kind' => 'image', 'mimes' => ['image/jpeg']],
        'png' => ['kind' => 'image', 'mimes' => ['image/png']],
        'webp' => ['kind' => 'image', 'mimes' => ['image/webp']],
        'mp4' => ['kind' => 'video', 'mimes' => ['video/mp4']],
        'mov' => ['kind' => 'video', 'mimes' => ['video/quicktime', 'video/mp4']],
        'webm' => ['kind' => 'video', 'mimes' => ['video/webm', 'audio/webm', 'video/x-matroska']],
        'txt' => ['kind' => 'text', 'mimes' => ['text/plain']],
        'pdf' => ['kind' => 'document', 'mimes' => ['application/pdf']],
        'docx' => ['kind' => 'document', 'mimes' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip']],
        'xlsx' => ['kind' => 'document', 'mimes' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip']],
        'pptx' => ['kind' => 'document', 'mimes' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip']],
        'zip' => ['kind' => 'archive', 'mimes' => ['application/zip']],
    ],

    // Maximum size per file, by kind.
    'max_kb' => [
        'image' => 10 * 1024,
        'video' => 50 * 1024,
        'text' => 2 * 1024,
        'document' => 20 * 1024,
        'archive' => 50 * 1024,
    ],

    // Decompression-bomb guard for images: a tiny PNG can declare enormous
    // dimensions and expand to gigabytes once decoded. Dimensions are read
    // from the file header before any decoding happens.
    'image_max_pixels' => 40_000_000,
    'image_max_side' => 10_000,

    'profiles' => [
        // Client-supplied samples on the public quote form — kept to inert
        // formats only. No archives or Office files from anonymous visitors.
        'quote_reference' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov', 'webm', 'txt', 'pdf'],
            'max_files' => 5,
            'max_total_kb' => 100 * 1024,
        ],

        // Staff deliverables on a milestone. Archives are accepted for handing
        // over work but are never extracted on the server.
        'milestone_submission' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov', 'webm', 'txt', 'pdf', 'docx', 'xlsx', 'pptx', 'zip'],
            'max_files' => 10,
            'max_total_kb' => 150 * 1024,
        ],
    ],

    // Optional antivirus. Shared hosting can't run an antivirus daemon, so
    // this is off by default; on a VPS with ClamAV installed, set
    // UPLOAD_CLAMAV_BINARY=clamdscan (or clamscan) and every upload is scanned
    // before it's accepted. If the scanner is configured but fails to run,
    // the upload is rejected rather than let through unscanned.
    'clamav_binary' => env('UPLOAD_CLAMAV_BINARY'),

];
