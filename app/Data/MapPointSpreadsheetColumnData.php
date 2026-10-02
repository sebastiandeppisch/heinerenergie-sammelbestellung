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
        /** The uuid of the form field, only set for MapPointSpreadsheetField::FIELD. */
        public ?string $form_field_id = null,
        /** The type of the field to create, only set for MapPointSpreadsheetField::NEW_CATEGORY_FIELD. */
        public ?FieldType $new_field_type = null,
    ) {}

    /**
     * Identifies what the column holds. Fields of a form definition are told apart by their id, all other fields by the field.
     */
    public function target(): string
    {
        return $this->field === MapPointSpreadsheetField::FIELD
            ? self::fieldTarget((string) $this->form_field_id)
            : $this->field->value;
    }

    public static function fieldTarget(string $formFieldId): string
    {
        return MapPointSpreadsheetField::FIELD->value.':'.$formFieldId;
    }
}
