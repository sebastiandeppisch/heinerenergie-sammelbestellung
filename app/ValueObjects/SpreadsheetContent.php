<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * The first sheet of a spreadsheet file: its header row and the data rows below.
 */
readonly class SpreadsheetContent
{
    /**
     * @param  array<int, string>  $headers  Unique within the sheet.
     * @param  array<int, array<int, mixed>>  $rows  Cells in the order of the headers. Empty rows are left out by Laravel Excel.
     */
    public function __construct(
        public array $headers,
        public array $rows,
    ) {}
}
