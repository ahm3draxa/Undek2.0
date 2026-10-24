<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Everything public pages (home, quote form, chatbot) read from the database,
 * cached so an anonymous visitor never needs a MySQL connection — shared
 * hosting allows 500 per hour for the whole site. Any edit to services,
 * skills or public team profiles flushes all of it.
 */
class PublicCache
{
    public const HOME = 'public.home';

    public const QUOTE_FORM = 'public.quote-form';

    public const CHATBOT_SERVICES = 'public.chatbot-services';

    private const TTL_MINUTES = 30;

    public static function remember(string $key, Closure $callback): mixed
    {
        return Cache::remember($key, now()->addMinutes(self::TTL_MINUTES), $callback);
    }

    public static function flush(): void
    {
        foreach ([self::HOME, self::QUOTE_FORM, self::CHATBOT_SERVICES] as $key) {
            Cache::forget($key);
        }
    }
}
