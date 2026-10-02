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
        /** The uuid of the characteristic, only set for MapPointSpreadsheetField::CHARACTERISTIC. */
        public ?string $characteristic_id = null,
    ) {}

    /**
     * Identifies what the column holds. Fields of a form definition and characteristics are told apart by their id,
     * all other fields by the field.
     */
    public function target(): string
    {
        return match ($this->field) {
            MapPointSpreadsheetField::FIELD => self::fieldTarget((string) $this->form_field_id),
            MapPointSpreadsheetField::CHARACTERISTIC => self::characteristicTarget((string) $this->characteristic_id),
            default => $this->field->value,
        };
    }

    public static function characteristicTarget(string $characteristicId): string
    {
        return MapPointSpreadsheetField::CHARACTERISTIC->value.':'.$characteristicId;
    }

    public static function fieldTarget(string $formFieldId): string
    {
        return MapPointSpreadsheetField::FIELD->value.':'.$formFieldId;
    }
}
