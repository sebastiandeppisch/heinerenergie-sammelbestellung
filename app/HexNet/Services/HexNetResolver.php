<?php

declare(strict_types=1);

namespace App\HexNet\Services;

use App\HexNet\Enums\HexNetStatus;
use App\HexNet\Models\HexNet;
use App\Models\MapPoint;

class HexNetResolver
{
    public function __construct(private readonly HexNetGridService $gridService) {}

    /**
     * The active HexaNetz the point counts in, if any.
     */
    public function forMapPoint(MapPoint $mapPoint): ?HexNet
    {
        if ($mapPoint->category_id === null || $mapPoint->coordinate === null) {
            return null;
        }

        return HexNet::query()
            ->where('status', HexNetStatus::ACTIVE)
            ->get()
            ->first(function (HexNet $hexNet) use ($mapPoint): bool {
                $grid = $hexNet->grid();

                return $grid !== null
                    && $hexNet->play_area !== null
                    && in_array((int) $mapPoint->category_id, $this->gridService->categoryIds($hexNet), true)
                    && $grid->contains($hexNet->play_area, $mapPoint->coordinate);
            });
    }
}
