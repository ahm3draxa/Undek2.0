<?php

namespace App\Rules;

use App\Services\Uploads\SecureUploader;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Validates one uploaded file against an upload profile in config/uploads.php.
 */
class SafeUpload implements ValidationRule
{
    public function __construct(private string $profile) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('The upload is not a valid file.');

            return;
        }

        $reason = app(SecureUploader::class)->rejectionReason($value, $this->profile);

        if ($reason) {
            $fail($value->getClientOriginalName().': '.$reason);
        }
    }

    /**
     * Rules for a multi-file input: per-file checks plus count and total size
     * limits, so many small files can't add up to an oversized request.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rulesFor(string $field, string $profile): array
    {
        $config = app(SecureUploader::class)->profile($profile);
        $uploader = app(SecureUploader::class);

        return [
            $field => [
                'nullable',
                'array',
                'max:'.$config['max_files'],
                function (string $attribute, mixed $files, Closure $fail) use ($config, $uploader) {
                    $total = collect($files)->sum(fn ($file) => $file instanceof UploadedFile ? $file->getSize() : 0);

                    if ($total > $config['max_total_kb'] * 1024) {
                        $fail('All files together can be at most '.$uploader->humanSize($config['max_total_kb'] * 1024).'.');
                    }
                },
            ],
            "$field.*" => ['required', new self($profile)],
        ];
    }
}
