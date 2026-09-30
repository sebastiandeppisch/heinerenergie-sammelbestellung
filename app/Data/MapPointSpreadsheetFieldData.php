<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\MapPointSpreadsheetField;
use App\Models\FormField;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MapPointSpreadsheetFieldData extends Data
{
    public function __construct(
        public MapPointSpreadsheetField $value,
        public string $label,
        public bool $is_key,
        /** The uuid of the category field, only set for MapPointSpreadsheetField::CATEGORY_FIELD. */
        public ?string $category_field_id = null,
        /** The uuid of the category defining the field, only set for MapPointSpreadsheetField::CATEGORY_FIELD. */
        public ?string $category_id = null,
    ) {}

    public static function fromEnum(MapPointSpreadsheetField $field): self
    {
        return new self(
            value: $field,
            label: $field->label(),
            is_key: $field->isKey(),
        );
    }

    /**
     * Named after its category, because categories may have fields with the same label.
     */
    public static function fromCategoryField(FormField $field): self
    {
        return new self(
            value: MapPointSpreadsheetField::CATEGORY_FIELD,
            label: self::categoryFieldLabel($field),
            is_key: false,
            category_field_id: $field->uuid,
            category_id: $field->formDefinition?->mapPointCategory?->uuid,
        );
    }

    public static function categoryFieldLabel(FormField $field): string
    {
        $categoryName = $field->formDefinition?->mapPointCategory?->name;

        return $categoryName === null ? $field->label : $categoryName.' › '.$field->label;
    }
}
