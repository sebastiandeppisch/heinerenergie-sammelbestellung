<?php

declare(strict_types=1);

namespace App\HexNet\Data;

use App\HexNet\Models\HexNet;
use App\ValueObjects\Coordinate;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * What the public page of a HexaNetz needs besides cells, counts and points.
 */
#[TypeScript]
class HexNetPublicData extends Data
{
    /**
     * @param  array<int, Coordinate>  $bounds  south west and north east of the play area
     */
    public function __construct(
        public string $id,
        public string $slug,
        public string $name,
        public string $title,
        public ?string $subtitle,
        public ?string $cta_text,
        public int $threshold_medium,
        public int $threshold_dark,
        public int $jitter_m,
        public int $hex_radius_m,
        public ?string $form_url,
        public array $bounds,
        public bool $is_draft,
    ) {}

    public static function fromModel(HexNet $hexNet): self
    {
        $coordinates = $hexNet->play_area->coordinates ?? [];
        $lats = array_map(fn (Coordinate $c): float => $c->lat, $coordinates);
        $lngs = array_map(fn (Coordinate $c): float => $c->lng, $coordinates);
        $hexNet->loadMissing('formDefinition');

        return new self(
            id: $hexNet->uuid,
            slug: $hexNet->slug,
            name: $hexNet->name,
            title: $hexNet->title ?: $hexNet->name,
            subtitle: $hexNet->subtitle,
            cta_text: $hexNet->cta_text,
            threshold_medium: $hexNet->threshold_medium,
            threshold_dark: $hexNet->threshold_dark,
            jitter_m: $hexNet->jitter_m,
            hex_radius_m: $hexNet->hex_radius_m,
            form_url: $hexNet->formDefinition !== null ? route('form.show', $hexNet->formDefinition) : null,
            bounds: $coordinates === [] ? [] : [new Coordinate(min($lats), min($lngs)), new Coordinate(max($lats), max($lngs))],
            is_draft: ! $hexNet->isActive(),
        );
    }
}
