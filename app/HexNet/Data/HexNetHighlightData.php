<?php

declare(strict_types=1);

namespace App\HexNet\Data;

use App\ValueObjects\Coordinate;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Shown after a person confirmed a new point that counts in a HexaNetz. The coordinate is moved by the jitter.
 */
#[TypeScript]
class HexNetHighlightData extends Data
{
    /**
     * @param  array<string, mixed>  $cells  GeoJSON FeatureCollection
     * @param  array<string, int>  $counts
     */
    public function __construct(
        public HexNetPublicData $hex_net,
        public array $cells,
        public array $counts,
        public HexNetStatsData $stats,
        public string $cell,
        public Coordinate $coordinate,
        public string $public_url,
        public bool $published,
    ) {}
}
