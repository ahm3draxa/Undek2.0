<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/**
 * Addresses that opted out of marketing email. Every campaign skips them,
 * whichever audience it targets.
 */
class MarketingUnsubscribe extends Model
{
    protected $fillable = ['email'];

    public static function isUnsubscribed(string $email): bool
    {
        return static::where('email', strtolower($email))->exists();
    }

    /**
     * Signed, so an address can only be unsubscribed from a link we sent to it.
     */
    public static function urlFor(string $email): string
    {
        return URL::signedRoute('marketing.unsubscribe', ['email' => strtolower($email)]);
    }
}
