<?php

declare(strict_types=1);

namespace App\HexNet\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class HexNetStatsData extends Data
{
    /**
     * @param  array<int, int>  $levels  number of cells per level: 0 open, 1 light, 2 medium, 3 dark
     */
    public function __construct(
        public int $percent,
        public int $playable_cells,
        public array $levels,
        public int $total_points,
    ) {}
}
