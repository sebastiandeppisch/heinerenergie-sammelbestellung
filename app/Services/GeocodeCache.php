<?php

declare(strict_types=1);

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache-aside helper for geocoding lookups that also caches "not found".
 *
 * Cache::rememberForever() never hands back a cached null, so an address that
 * OpenStreetMap does not know would hit Nominatim again on every single call.
 * This stores false as the sentinel for "not found", which the cache does
 * return.
 */
class GeocodeCache
{
    /**
     * A hit does not change, so it is kept indefinitely. A miss may well be
     * added to OpenStreetMap later, so it is only trusted for a week.
     */
    private const NOT_FOUND_DAYS = 7;

    /**
     * @template TValue
     *
     * @param  Closure(): (TValue|null)  $resolve
     * @return TValue|null
     */
    public function remember(string $key, Closure $resolve): mixed
    {
        $cached = Cache::get($key);

        if ($cached === false) {
            return null;
        }

        if ($cached !== null) {
            return $cached;
        }

        $value = $resolve();

        if ($value === null) {
            Cache::put($key, false, now()->addDays(self::NOT_FOUND_DAYS));
        } else {
            Cache::forever($key, $value);
        }

        return $value;
    }
}
