<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    public const INTERNSHIP_CERTIFICATE = 'internship_certificate';

    public const EXPERIENCE_LETTER = 'experience_letter';

    public const EARNINGS_LETTER = 'earnings_letter';

    public const TYPES = [
        self::INTERNSHIP_CERTIFICATE => 'Internship Certificate',
        self::EXPERIENCE_LETTER => 'Experience Letter',
        self::EARNINGS_LETTER => 'Earnings Letter',
    ];

    protected $fillable = [
        'user_id',
        'type',
        'code',
        'snapshot',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'snapshot' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateCode(): string
    {
        do {
            $code = 'UNDEK-'.now()->format('Y').'-'.Str::upper(Str::random(8));
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
