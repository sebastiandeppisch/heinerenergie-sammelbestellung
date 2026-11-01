<?php

declare(strict_types=1);

namespace App\HexNet\Observers;

use App\HexNet\Services\HexNetGridService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Counts depend on category, coordinate and existence of points, so any change invalidates them. After the commit,
 * so a request reading in between cannot cache counts from before the change.
 */
class MapPointObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(): void
    {
        HexNetGridService::invalidatePoints();
    }

    public function deleted(): void
    {
        HexNetGridService::invalidatePoints();
    }
}
