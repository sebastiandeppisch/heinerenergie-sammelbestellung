<?php

declare(strict_types=1);

namespace App\Imports;

use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

/**
 * Reads the raw cells of a spreadsheet for the column mapping and its preview.
 *
 * Empty rows are left out by Laravel Excel. Headings are deliberately not parsed by it, because the
 * heading row formatter would turn a header like "Anlagengröße" into a slugged array key and two columns
 * with the same header would collapse into one. SpreadsheetReader interprets the header row instead.
 */
class RawSpreadsheetImport implements SkipsEmptyRows, ToArray, WithCalculatedFormulas
{
    /**
     * The rows are read through Excel::toArray(), so nothing has to happen per sheet.
     *
     * @param  array<array-key, mixed>  $array
     */
    public function array(array $array): void {}
}
