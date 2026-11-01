<?php

declare(strict_types=1);

namespace App\HexNet\Listeners;

use App\HexNet\Enums\HexNetStatus;
use App\HexNet\Models\HexNet;
use App\Models\MapPointCategory;

/**
 * Deleting a category drops it from its HexaNetze. A running HexaNetz without any category left would show an empty
 * game, so it goes back to draft until an admin chooses new categories.
 */
class PauseHexNetsWithoutCategories
{
    public function handle(MapPointCategory $category): void
    {
        HexNet::query()
            ->where('status', HexNetStatus::ACTIVE)
            ->whereHas('categories', fn ($query) => $query->whereKey($category->id))
            ->whereDoesntHave('categories', fn ($query) => $query->whereKeyNot($category->id))
            ->update(['status' => HexNetStatus::DRAFT]);
    }
}
