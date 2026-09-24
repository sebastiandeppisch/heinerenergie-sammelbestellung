<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\SpreadsheetFormat;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SpreadsheetFormatData extends Data
{
    public function __construct(
        public SpreadsheetFormat $value,
        public string $label,
    ) {}

    public static function fromEnum(SpreadsheetFormat $format): self
    {
        return new self(
            value: $format,
            label: $format->label(),
        );
    }
}
