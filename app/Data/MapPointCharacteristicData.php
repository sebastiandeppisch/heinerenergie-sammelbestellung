<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\FormField;
use App\Models\MapPointCharacteristic;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A characteristic of map points, called "Maßnahme" in the UI.
 */
#[TypeScript]
class MapPointCharacteristicData extends Data
{
    /**
     * @param  array<int, MapPointCharacteristicFieldData>|null  $fields  only set where characteristics are managed
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $icon_path,
        public ?string $color,
        public string $category_id,
        public string $category_name,
        /** Only set where characteristics are managed. */
        public ?int $map_points_count = null,
        public ?array $fields = null,
    ) {}

    /**
     * Needs the relation category.
     */
    public static function fromModel(MapPointCharacteristic $characteristic): self
    {
        return new self(
            id: $characteristic->uuid,
            name: $characteristic->name,
            icon_path: $characteristic->icon_path !== null ? asset('storage/'.$characteristic->icon_path) : null,
            color: $characteristic->color,
            category_id: $characteristic->category->uuid,
            category_name: $characteristic->category->name,
        );
    }

    /**
     * With the number of points and the own fields, for the category page. Needs the relations category,
     * formDefinition.fields and the count of mapPoints.
     *
     * @param  array<int, int>  $publicFieldIds
     */
    public static function forManagement(MapPointCharacteristic $characteristic, array $publicFieldIds): self
    {
        $data = self::fromModel($characteristic);
        $data->map_points_count = $characteristic->map_points_count ?? 0;
        $data->fields = $characteristic->formDefinition === null ? [] : $characteristic->formDefinition->fields
            ->map(fn (FormField $field): MapPointCharacteristicFieldData => new MapPointCharacteristicFieldData(
                id: $field->uuid,
                label: $field->label,
                is_public: in_array($field->id, $publicFieldIds, true),
            ))
            ->values()
            ->all();

        return $data;
    }
}
