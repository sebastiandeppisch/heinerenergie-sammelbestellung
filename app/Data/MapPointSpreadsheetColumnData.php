<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\FieldType;
use App\Enums\MapPointSpreadsheetField;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MapPointSpreadsheetColumnData extends Data
{
    public function __construct(
        public string $header,
        public MapPointSpreadsheetField $field,
        /** The uuid of the category field, only set for MapPointSpreadsheetField::CATEGORY_FIELD. */
        public ?string $category_field_id = null,
        /** The type of the field to create, only set for MapPointSpreadsheetField::NEW_CATEGORY_FIELD. */
        public ?FieldType $new_field_type = null,
    ) {}

    /**
     * Identifies what the column holds. Category fields are told apart by their id, all other fields by the field.
     */
    public function target(): string
    {
        return $this->field === MapPointSpreadsheetField::CATEGORY_FIELD
            ? self::categoryFieldTarget((string) $this->category_field_id)
            : $this->field->value;
    }

    public static function categoryFieldTarget(string $categoryFieldId): string
    {
        return MapPointSpreadsheetField::CATEGORY_FIELD->value.':'.$categoryFieldId;
    }
}
