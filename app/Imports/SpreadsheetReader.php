<?php

declare(strict_types=1);

namespace App\Imports;

use App\Enums\SpreadsheetFormat;
use App\ValueObjects\SpreadsheetContent;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;

/**
 * Reads the first sheet of a spreadsheet file into headers and data rows, for the column mapping and its
 * preview. It knows nothing about what the rows describe, so every spreadsheet import can use it.
 *
 * The header row is read here instead of through WithHeadingRow, because the heading row formatter turns
 * headers into slugged array keys and would let two columns with the same header collapse into one.
 * The import itself runs through Laravel Excel, see MapPointsImport.
 */
class SpreadsheetReader
{
    /**
     * @throws ValidationException When the file cannot be read, has no header row or repeats a header.
     */
    public function read(string $path, string $disk): SpreadsheetContent
    {
        try {
            $sheets = Excel::toArray(new RawSpreadsheetImport, $path, $disk, SpreadsheetFormat::fromFilename($path)?->excelType());
        } catch (SpreadsheetException) {
            throw ValidationException::withMessages(['file' => 'Die Datei konnte nicht gelesen werden.']);
        }

        $sheetRows = array_values(is_array($sheets[0] ?? null) ? $sheets[0] : []);
        $headers = $this->headers(is_array($sheetRows[0] ?? null) ? array_values($sheetRows[0]) : []);

        // Rows that end early would leave gaps, so every row is brought to the shape of the header row.
        $rows = array_map(
            fn (mixed $cells): array => array_slice(array_pad(is_array($cells) ? array_values($cells) : [], count($headers), null), 0, count($headers)),
            array_slice($sheetRows, 1),
        );

        return new SpreadsheetContent($headers, $rows);
    }

    /**
     * Columns are told apart by their header, for example when a saved mapping is applied, so headers must be unique.
     *
     * @param  array<int, mixed>  $headerCells
     * @return array<int, string>
     */
    private function headers(array $headerCells): array
    {
        while ($headerCells !== [] && SpreadsheetCell::text($headerCells[array_key_last($headerCells)]) === null) {
            array_pop($headerCells);
        }

        if ($headerCells === []) {
            throw ValidationException::withMessages(['file' => 'Die Datei enthält keine Kopfzeile.']);
        }

        $headers = [];
        foreach ($headerCells as $index => $cell) {
            $headers[] = SpreadsheetCell::text($cell) ?? 'Spalte '.($index + 1);
        }

        $duplicates = collect($headers)
            ->groupBy(fn (string $header): string => Str::lower($header))
            ->filter(fn (Collection $sameHeaders): bool => $sameHeaders->count() > 1)
            ->map(fn (Collection $sameHeaders): string => '„'.$sameHeaders->first().'“');

        if ($duplicates->isNotEmpty()) {
            throw ValidationException::withMessages([
                'file' => 'Jede Spaltenüberschrift darf nur einmal vorkommen. Mehrfach vorhanden: '.$duplicates->implode(', ').'.',
            ]);
        }

        return $headers;
    }
}
