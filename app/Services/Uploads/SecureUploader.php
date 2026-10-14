<?php

namespace App\Services\Uploads;

use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The single gate every user upload goes through. See config/uploads.php for
 * the rules themselves; this class enforces them and stores what passes.
 */
class SecureUploader
{
    public const DISK = 'local';

    /**
     * Why this file can't be accepted under the given profile, or null if it can.
     * Only reads file headers and bytes — nothing is decoded, executed or
     * extracted at this stage.
     */
    public function rejectionReason(UploadedFile $file, string $profile): ?string
    {
        if (! $file->isValid()) {
            return 'The file did not upload completely. Please try again.';
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $allowed = $this->profile($profile)['extensions'];

        if (! in_array($extension, $allowed, true)) {
            return 'Files of type .'.($extension ?: '?').' are not accepted. Allowed: '.implode(', ', $allowed).'.';
        }

        $type = config("uploads.types.$extension");
        $path = $file->getRealPath();

        if ($file->getSize() === 0) {
            return 'The file is empty.';
        }

        if ($file->getSize() > config("uploads.max_kb.{$type['kind']}") * 1024) {
            return sprintf('%s files can be at most %s.', ucfirst($type['kind']), $this->humanSize(config("uploads.max_kb.{$type['kind']}") * 1024));
        }

        // The real content type, sniffed from the file's bytes — the name and
        // the browser-reported type are both trivially faked.
        $sniffed = (new finfo(FILEINFO_MIME_TYPE))->file($path);

        if (! in_array($sniffed, $type['mimes'], true)) {
            return 'The file\'s contents don\'t match its .'.$extension.' extension, so it was rejected.';
        }

        if ($type['kind'] === 'image' && ($reason = $this->imageRejectionReason($path))) {
            return $reason;
        }

        if ($this->isInfected($path)) {
            return 'The file was flagged by the virus scanner and rejected.';
        }

        return null;
    }

    /**
     * Stores an already-validated file privately under a random name.
     * Images are decoded and re-encoded first, which drops anything appended
     * to or hidden inside the original file (and its EXIF metadata, such as
     * GPS location) — only the pixels survive.
     *
     * @return array{disk: string, path: string, original_name: string, mime_type: string, size: int}
     */
    public function store(UploadedFile $file, string $directory): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $type = config("uploads.types.$extension");
        $name = Str::uuid().'.'.$extension;
        $directory = trim($directory, '/').'/'.now()->format('Y/m');

        if ($type['kind'] === 'image') {
            $contents = $this->reencodeImage($file->getRealPath(), $extension);
            Storage::disk(self::DISK)->put("$directory/$name", $contents);
            $size = strlen($contents);
        } else {
            Storage::disk(self::DISK)->putFileAs($directory, $file, $name);
            $size = $file->getSize();
        }

        return [
            'disk' => self::DISK,
            'path' => "$directory/$name",
            'original_name' => $this->cleanName($file->getClientOriginalName()),
            // Our own canonical type for the extension, never the client's claim.
            'mime_type' => $type['mimes'][0],
            'size' => $size,
        ];
    }

    public function profile(string $profile): array
    {
        return config("uploads.profiles.$profile") ?? throw new RuntimeException("Unknown upload profile [$profile].");
    }

    /**
     * The value for an <input type="file" accept="..."> on a given profile.
     */
    public function acceptAttribute(string $profile): string
    {
        return collect($this->profile($profile)['extensions'])->map(fn ($ext) => ".$ext")->implode(',');
    }

    public function humanSize(int $bytes): string
    {
        return $bytes >= 1024 * 1024
            ? round($bytes / 1024 / 1024, 1).' MB'
            : max(1, round($bytes / 1024)).' KB';
    }

    private function imageRejectionReason(string $path): ?string
    {
        // getimagesize() reads only the header, so a decompression bomb is
        // caught here before a single pixel is decoded.
        $info = @getimagesize($path);

        if (! $info) {
            return 'The image could not be read.';
        }

        [$width, $height] = $info;

        if ($width > config('uploads.image_max_side') || $height > config('uploads.image_max_side')
            || $width * $height > config('uploads.image_max_pixels')) {
            return sprintf('The image is too large (%d×%d). Please resize it to under %d megapixels.', $width, $height, config('uploads.image_max_pixels') / 1_000_000);
        }

        // Decoding needs roughly 5 bytes per pixel; refuse rather than let PHP
        // run out of memory mid-request.
        if (! $this->hasMemoryFor($width * $height * 5 * 2)) {
            return 'The image is too large to process. Please upload a smaller version.';
        }

        return null;
    }

    private function reencodeImage(string $path, string $extension): string
    {
        $image = match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'webp' => @imagecreatefromwebp($path),
        };

        if (! $image) {
            throw new RuntimeException('Image could not be decoded.');
        }

        if (in_array($extension, ['jpg', 'jpeg'], true)) {
            $image = $this->applyExifOrientation($image, $path);
        }

        imagesavealpha($image, true);

        ob_start();
        match ($extension) {
            'jpg', 'jpeg' => imagejpeg($image, null, 90),
            'png' => imagepng($image, null, 6),
            'webp' => imagewebp($image, null, 90),
        };
        $contents = ob_get_clean();
        imagedestroy($image);

        return $contents;
    }

    /**
     * Re-encoding drops EXIF, including the orientation flag phones rely on,
     * so bake the rotation into the pixels first or portrait photos come out
     * sideways.
     */
    private function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        $orientation = function_exists('exif_read_data') ? (@exif_read_data($path)['Orientation'] ?? 1) : 1;

        $rotated = match ((int) $orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    private function isInfected(string $path): bool
    {
        $binary = config('uploads.clamav_binary');

        if (! $binary) {
            return false;
        }

        $result = Process::timeout(60)->run([$binary, '--no-summary', $path]);

        // ClamAV exit codes: 0 clean, 1 infected, anything else an error. An
        // error fails closed — a scanner that can't scan isn't a pass.
        if ($result->exitCode() === 0) {
            return false;
        }

        if ($result->exitCode() !== 1) {
            report(new RuntimeException('Virus scan failed to run: '.$result->errorOutput()));
        }

        return true;
    }

    private function hasMemoryFor(int $bytes): bool
    {
        $limit = ini_get('memory_limit');

        if ($limit === '-1' || $limit === false) {
            return true;
        }

        $limitBytes = (int) $limit * match (strtolower(substr($limit, -1))) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };

        return memory_get_usage(true) + $bytes < $limitBytes;
    }

    private function cleanName(string $name): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? 'file';

        return Str::limit($name, 200, '');
    }
}
