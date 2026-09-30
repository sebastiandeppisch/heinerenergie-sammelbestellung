<?php

declare(strict_types=1);

namespace App\Imports;

use App\Enums\FieldType;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\MapPointField;
use App\Models\MapPointFieldOption;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;

/**
 * Writes and reads the values of category fields in spreadsheet cells. Options are written with their label,
 * because that is what people read and type. Reading accepts the label as well as the stored value, so files
 * exported by us and files written by hand can both be imported.
 */
final class MapPointFieldCell
{
    private const array DATE_FORMATS = ['d.m.Y', 'j.n.Y', 'd.m.y', 'Y-m-d'];

    public static function export(MapPointField $field): string|int|float|null
    {
        $value = $field->value;

        if ($field->type->supportsOptions()) {
            $labels = $field->options->mapWithKeys(fn (MapPointFieldOption $option): array => [$option->value => $option->label]);

            return collect((array) $value)->map(fn (mixed $optionValue): string => (string) ($labels[$optionValue] ?? $optionValue))->implode(', ');
        }

        if ($field->type === FieldType::NUMBER && is_numeric($value)) {
            return $value + 0;
        }

        if ($field->type === FieldType::DATE && is_string($value)) {
            try {
                return Carbon::parse($value)->format('d.m.Y');
            } catch (Throwable) {
                return $value;
            }
        }

        return is_array($value) ? implode(', ', $value) : $value;
    }

    /**
     * The value to store, or null for an empty cell.
     *
     * @throws InvalidArgumentException When the cell holds nothing the field can store, with a message for the user.
     */
    public static function import(FormField $field, mixed $cell): mixed
    {
        if (SpreadsheetCell::text($cell) === null) {
            return null;
        }

        return match ($field->type) {
            FieldType::NUMBER => self::number($cell),
            FieldType::DATE => self::date($cell),
            FieldType::SELECT, FieldType::RADIO => self::option($field, (string) SpreadsheetCell::text($cell)),
            FieldType::CHECKBOX => array_map(
                fn (string $label): string => self::option($field, $label),
                array_values(array_filter(array_map(trim(...), preg_split('/[,;]/', (string) SpreadsheetCell::text($cell)) ?: []))),
            ),
            default => SpreadsheetCell::text($cell),
        };
    }

    private static function number(mixed $cell): int|float
    {
        $number = SpreadsheetCell::decimal($cell) ?? throw new InvalidArgumentException('Keine Zahl.');

        return floor($number) === $number ? (int) $number : $number;
    }

    /**
     * Excel stores a date as the number of days since 1900, text is read in German or ISO notation.
     */
    private static function date(mixed $cell): string
    {
        if (is_int($cell) || is_float($cell)) {
            return Date::excelToDateTimeObject($cell)->format('Y-m-d');
        }

        $text = (string) SpreadsheetCell::text($cell);

        foreach (self::DATE_FORMATS as $format) {
            $date = Carbon::createFromFormat('!'.$format, $text);

            if ($date !== null && $date->format($format) === $text) {
                return $date->format('Y-m-d');
            }
        }

        throw new InvalidArgumentException('Kein gültiges Datum, erwartet wird z. B. 31.12.2026.');
    }

    private static function option(FormField $field, string $text): string
    {
        $option = $field->options->first(
            fn (FormFieldOption $option): bool => Str::lower($option->label) === Str::lower($text) || Str::lower($option->value) === Str::lower($text),
        );

        if ($option === null) {
            throw new InvalidArgumentException("„{$text}“ ist keine der Optionen: ".$field->options->pluck('label')->implode(', ').'.');
        }

        return $option->value;
    }
}
