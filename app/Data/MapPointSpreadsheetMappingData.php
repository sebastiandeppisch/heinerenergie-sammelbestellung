<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\MapPointSpreadsheetField;
use App\Models\Group;
use App\Models\MapPointSpreadsheetMapping;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MapPointSpreadsheetMappingData extends Data
{
    /**
     * @param  array<int, MapPointSpreadsheetColumnData>  $columns
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $columns,
        public MapPointSpreadsheetField $key_field,
    ) {}

    public static function fromModel(MapPointSpreadsheetMapping $mapping): self
    {
        return new self(
            id: $mapping->uuid,
            name: $mapping->name,
            columns: array_map(
                fn (array $column): MapPointSpreadsheetColumnData => new MapPointSpreadsheetColumnData(
                    header: $column['header'],
                    field: MapPointSpreadsheetField::from($column['field']),
                ),
                $mapping->columns,
            ),
            key_field: $mapping->key_field,
        );
    }

    /**
     * @return array<int, self>
     */
    public static function forGroup(Group $group): array
    {
        return MapPointSpreadsheetMapping::query()
            ->ownedByGroup($group)
            ->orderBy('name')
            ->get()
            ->map(fn (MapPointSpreadsheetMapping $mapping): self => self::fromModel($mapping))
            ->all();
    }
}
