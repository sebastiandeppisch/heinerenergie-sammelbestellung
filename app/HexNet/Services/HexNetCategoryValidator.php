<?php

declare(strict_types=1);

namespace App\HexNet\Services;

use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;

/**
 * The categories of a HexaNetz come from one subtree and offer the same characteristics, so every point can be
 * shown with the same measures.
 */
class HexNetCategoryValidator
{
    /**
     * @param  array<int, int>  $categoryIds
     * @return string|null the error, null if valid
     */
    public function validate(array $categoryIds): ?string
    {
        if ($categoryIds === []) {
            return 'Wähle mindestens eine Kategorie.';
        }

        $tree = MapPointCategory::tree();
        $roots = [];
        $characteristicSets = [];

        foreach ($categoryIds as $categoryId) {
            $ancestors = $tree->ancestorIds($categoryId);
            $roots[end($ancestors) ?: $categoryId] = true;

            $ids = MapPointCharacteristic::selectableFor($categoryId, $tree)->pluck('id')->sort()->values()->all();
            $characteristicSets[implode(',', $ids)] = true;
        }

        if (count($roots) > 1) {
            return 'Die Kategorien müssen aus einem gemeinsamen Teilbaum stammen.';
        }

        if (count($characteristicSets) > 1) {
            return 'Alle Kategorien müssen dieselben Maßnahmen haben. Lege die Maßnahmen an der gemeinsamen Hauptkategorie an.';
        }

        if (array_key_first($characteristicSets) === '') {
            return 'Die Kategorien haben noch keine Maßnahmen.';
        }

        return null;
    }
}
