<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\MapPointSpreadsheetField;
use App\Models\FormField;
use App\Models\MapPointCharacteristic;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MapPointSpreadsheetFieldData extends Data
{
    public function __construct(
        public MapPointSpreadsheetField $value,
        public string $label,
        public bool $is_key,
        /** The uuid of the form field, only set for MapPointSpreadsheetField::FIELD. */
        public ?string $form_field_id = null,
        /** The uuid of the category defining the field or characteristic, only set for MapPointSpreadsheetField::FIELD and CHARACTERISTIC. */
        public ?string $category_id = null,
        /** The uuid of the characteristic, set for MapPointSpreadsheetField::CHARACTERISTIC and for the fields of a characteristic. */
        public ?string $characteristic_id = null,
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
     * Named after its category and characteristic, because they may have fields with the same label. Needs the
     * relations formDefinition.mapPointCategory and formDefinition.mapPointCharacteristic.category.
     */
    public static function fromField(FormField $field): self
    {
        $characteristic = $field->formDefinition?->mapPointCharacteristic;

        return new self(
            value: MapPointSpreadsheetField::FIELD,
            label: self::fieldLabel($field),
            is_key: false,
            form_field_id: $field->uuid,
            category_id: ($field->formDefinition->mapPointCategory ?? $characteristic?->category)?->uuid,
            characteristic_id: $characteristic?->uuid,
        );
    }

    /**
     * Needs the relation category.
     */
    public static function fromCharacteristic(MapPointCharacteristic $characteristic): self
    {
        return new self(
            value: MapPointSpreadsheetField::CHARACTERISTIC,
            label: self::characteristicLabel($characteristic),
            is_key: false,
            category_id: $characteristic->category->uuid,
            characteristic_id: $characteristic->uuid,
        );
    }

    /**
     * "Kategorie › Feld" for fields of a category, "Kategorie › Maßnahme › Feld" for fields of a characteristic.
     */
    public static function fieldLabel(FormField $field): string
    {
        $characteristic = $field->formDefinition?->mapPointCharacteristic;

        if ($characteristic !== null) {
            return $characteristic->category->name.' › '.$characteristic->name.' › '.$field->label;
        }

        $categoryName = $field->formDefinition?->mapPointCategory?->name;

        return $categoryName === null ? $field->label : $categoryName.' › '.$field->label;
    }

    public static function characteristicLabel(MapPointCharacteristic $characteristic): string
    {
        return $characteristic->category->name.' › Maßnahme '.$characteristic->name;
    }
}
