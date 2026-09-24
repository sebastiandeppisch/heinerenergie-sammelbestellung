<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\MapPointSpreadsheetField;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MapPointSpreadsheetFieldData extends Data
{
    public function __construct(
        public MapPointSpreadsheetField $value,
        public string $label,
        public bool $is_key,
    ) {}

    public static function fromEnum(MapPointSpreadsheetField $field): self
    {
        return new self(
            value: $field,
            label: $field->label(),
            is_key: $field->isKey(),
        );
    }
}
