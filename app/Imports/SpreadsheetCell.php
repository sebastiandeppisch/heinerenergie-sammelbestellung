<?php

declare(strict_types=1);

namespace App\Imports;

use Illuminate\Support\Str;

/**
 * Reads and writes single cell values the way people fill in spreadsheets by hand.
 *
 * Laravel Excel hands over what a file holds and has no opinion on any of this: a decimal comma as
 * written by German Excel, "ja" and "nein" as a boolean, or the apostrophe that keeps a text from being
 * run as a formula. Only the escaping is ours as well on the way out, because CSV files have no cell types.
 */
final class SpreadsheetCell
{
    /**
     * Characters that make spreadsheet programs treat a text as a formula.
     */
    private const array FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Trimmed text, or null for an empty cell. The apostrophe written by escapeFormula() is removed again.
     */
    public static function text(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $text = Str::trim((string) $value);

        if (Str::startsWith($text, "'") && in_array(Str::substr($text, 1, 1), self::FORMULA_PREFIXES, true)) {
            $text = Str::substr($text, 1);
        }

        return $text === '' ? null : $text;
    }

    /**
     * Accepts numbers from spreadsheet cells as well as text with a decimal comma, as written by German Excel.
     */
    public static function decimal(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $normalized = Str::replace(',', '.', self::text($value) ?? '');

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    /**
     * An empty cell counts as no, a value that is neither yes nor no gives null.
     */
    public static function boolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return match (Str::lower(self::text($value) ?? '')) {
            'ja', 'j', 'yes', 'y', 'true', 'wahr', 'x', '1' => true,
            'nein', 'n', 'no', 'false', 'falsch', '0', '' => false,
            default => null,
        };
    }

    /**
     * Prefixes text that a spreadsheet program would run as a formula with an apostrophe.
     * CSV files have no cell types, so this is the only way to keep a title like "=HYPERLINK(...)" harmless.
     */
    public static function escapeFormula(string $text): string
    {
        return in_array(Str::substr($text, 0, 1), self::FORMULA_PREFIXES, true) ? "'".$text : $text;
    }
}
