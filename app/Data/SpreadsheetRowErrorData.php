<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SpreadsheetRowErrorData extends Data
{
    /**
     * @param  int  $row  The row number as shown in the spreadsheet, the header being row 1.
     * @param  string|null  $column  The header of the offending column, null if the whole row is affected.
     */
    public function __construct(
        public int $row,
        public ?string $column,
        public string $message,
    ) {}
}
