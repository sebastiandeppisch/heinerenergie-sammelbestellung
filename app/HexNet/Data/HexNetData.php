<?php

declare(strict_types=1);

namespace App\HexNet\Data;

use App\HexNet\Enums\HexNetStatus;
use App\HexNet\Models\HexNet;
use App\Models\MapPointCategory;
use App\ValueObjects\Coordinate;
use App\ValueObjects\Polygon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A HexaNetz as admins edit it.
 */
#[TypeScript]
class HexNetData extends Data
{
    /**
     * @param  array<int, string>  $category_ids
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public HexNetStatus $status,
        public string $status_label,
        public ?string $title,
        public ?string $subtitle,
        public ?string $cta_text,
        public ?Polygon $play_area,
        public ?Coordinate $grid_origin,
        public int $hex_radius_m,
        public bool $grid_locked,
        public int $threshold_medium,
        public int $threshold_dark,
        public int $jitter_m,
        public string $group_id,
        public string $group_name,
        public array $category_ids,
        public ?string $form_definition_id,
        public ?string $form_definition_name,
        public string $public_url,
        public ?HexNetStatsData $stats = null,
    ) {}

    /**
     * Needs the relations group, categories and formDefinition.
     */
    public static function fromModel(HexNet $hexNet, ?HexNetStatsData $stats = null): self
    {
        return new self(
            id: $hexNet->uuid,
            name: $hexNet->name,
            slug: $hexNet->slug,
            status: $hexNet->status,
            status_label: $hexNet->status->label(),
            title: $hexNet->title,
            subtitle: $hexNet->subtitle,
            cta_text: $hexNet->cta_text,
            play_area: $hexNet->play_area,
            grid_origin: $hexNet->origin(),
            hex_radius_m: $hexNet->hex_radius_m,
            grid_locked: $hexNet->grid_locked,
            threshold_medium: $hexNet->threshold_medium,
            threshold_dark: $hexNet->threshold_dark,
            jitter_m: $hexNet->jitter_m,
            group_id: $hexNet->group->uuid,
            group_name: $hexNet->group->name,
            category_ids: $hexNet->categories->map(fn (MapPointCategory $category): string => $category->uuid)->values()->all(),
            form_definition_id: $hexNet->formDefinition?->uuid,
            form_definition_name: $hexNet->formDefinition?->name,
            public_url: route('hexnet.public', $hexNet->slug),
            stats: $stats,
        );
    }
}
