<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NominatimUnavailableException;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Sleep;

/**
 * Keeps the application within the OpenStreetMap usage policy by handing out
 * request slots that are spaced apart by a fixed interval.
 *
 * A caller reserves the next free slot under a short lock and then waits on its
 * own. The lock is never held while the HTTP request runs, so a hanging request
 * cannot block anybody else.
 */
class NominatimThrottle
{
    private const string LOCK_KEY = 'nominatim:throttle:lock';

    private const string NEXT_SLOT_KEY = 'nominatim:throttle:next-slot';

    /**
     * How long a reservation may keep the lock before it is considered stale.
     */
    private const int LOCK_TTL_SECONDS = 10;

    /**
     * How long a caller waits for its turn to reserve a slot.
     */
    private const int LOCK_WAIT_SECONDS = 5;

    public function __construct(
        private readonly float $interval,
        private readonly float $maxWait,
    ) {}

    public function interval(): float
    {
        return $this->interval;
    }

    public function maxWait(): float
    {
        return $this->maxWait;
    }

    /**
     * How long a request would have to wait for a free slot right now. Zero
     * means nothing is queued up. Anything approaching maxWait() means requests
     * are backing up and will soon be refused.
     */
    public function currentWait(): float
    {
        $slot = (float) Cache::get(self::NEXT_SLOT_KEY, 0.0);

        return max(0.0, $slot - $this->now());
    }

    /**
     * Blocks until this caller may send its request.
     *
     * @throws NominatimUnavailableException if too many requests are already queued up
     */
    public function await(): void
    {
        $waitSeconds = $this->reserveSlot() - $this->now();

        if ($waitSeconds > 0) {
            Sleep::usleep((int) round($waitSeconds * 1_000_000));
        }
    }

    /**
     * Claims the next free slot and returns the timestamp it becomes due.
     *
     * @throws NominatimUnavailableException
     */
    private function reserveSlot(): float
    {
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TTL_SECONDS);

        try {
            $lock->block(self::LOCK_WAIT_SECONDS);
        } catch (LockTimeoutException) {
            throw NominatimUnavailableException::tooManyPendingRequests();
        }

        try {
            $now = $this->now();
            $slot = max($now, (float) Cache::get(self::NEXT_SLOT_KEY, 0.0));

            if ($slot - $now > $this->maxWait) {
                throw NominatimUnavailableException::tooManyPendingRequests();
            }

            Cache::put(self::NEXT_SLOT_KEY, $slot + $this->interval, now()->addMinutes(5));

            return $slot;
        } finally {
            $lock->release();
        }
    }

    /**
     * Carbon rather than microtime, so the throttle is deterministic under
     * Carbon::setTestNow() in tests.
     */
    private function now(): float
    {
        return Carbon::now()->getPreciseTimestamp(6) / 1_000_000;
    }
}
