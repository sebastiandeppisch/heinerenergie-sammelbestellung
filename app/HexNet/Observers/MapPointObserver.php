<?php

declare(strict_types=1);

namespace App\HexNet\Observers;

use App\HexNet\Services\HexNetGridService;

/**
 * Counts depend on category, coordinate and existence of points, so any change invalidates them.
 */
class MapPointObserver
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
