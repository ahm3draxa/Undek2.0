<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait StreamsStoredFiles
{
    /**
     * Serves a stored upload. Images, video and plain text can be shown in the
     * browser; everything else is forced to download. Either way the browser
     * is told not to second-guess the content type (nosniff) and the response
     * is sandboxed, so even a file that slipped through can't run script in
     * the portal's origin.
     */
    protected function streamStoredFile(string $disk, string $path, string $name, ?string $mime): StreamedResponse
    {
        abort_unless(Storage::disk($disk)->exists($path), 404);

        $mime = $mime ?: 'application/octet-stream';
        $inline = str_starts_with($mime, 'image/') || str_starts_with($mime, 'video/') || $mime === 'text/plain';

        $headers = [
            'Content-Type' => $mime === 'text/plain' ? 'text/plain; charset=UTF-8' : $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; media-src 'self'; style-src 'unsafe-inline'; sandbox",
            'Cache-Control' => 'private, max-age=3600',
        ];

        return $inline
            ? Storage::disk($disk)->response($path, $name, $headers, 'inline')
            : Storage::disk($disk)->download($path, $name, $headers);
    }
}
