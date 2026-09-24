<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\MapPointSpreadsheetField;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MapPointSpreadsheetColumnData extends Data
{
    public function __construct(
        public string $header,
        public MapPointSpreadsheetField $field,
    ) {}
}
