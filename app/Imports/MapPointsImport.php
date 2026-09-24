<?php

declare(strict_types=1);

namespace App\Imports;

use App\Data\MapPointImportRowData;
use App\Data\MapPointSpreadsheetColumnData;
use App\Data\SpreadsheetRowErrorData;
use App\Enums\MapPointSpreadsheetField;
use App\Exceptions\SpreadsheetValueException;
use App\Models\Group;
use App\Models\MapPoint;
use App\ValueObjects\Coordinate;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Imports the rows of a spreadsheet as map points of a group, using the column mapping the user chose.
 *
 * Laravel Excel reads the file, skips empty rows, validates every row against the rules below and calls
 * onRow() only for the rows that passed, together with their row number in the file. What is specific to
 * map points happens here: finding the point a row refers to and resolving its category.
 */
class MapPointsImport implements OnEachRow, SkipsEmptyRows, SkipsOnFailure, WithCalculatedFormulas, WithMultipleSheets, WithStartRow, WithValidation
{
    /** @var array<int, MapPointImportRowData> */
    private array $importedRows = [];

    /** @var array<int, SpreadsheetRowErrorData> */
    private array $errors = [];

    /**
     * The column of every mapped field, keyed by the field.
     *
     * @var array<string, int>
     */
    private readonly array $columnsByField;

    /**
     * @param  array<int, MapPointSpreadsheetColumnData>  $columns  One entry per spreadsheet column, in the order of the file.
     * @param  bool  $defaultPublished  Whether created points are public. A mapped published column decides per row instead.
     */
    public function __construct(
        private readonly array $columns,
        private readonly MapPointKeyMatcher $matcher,
        private readonly MapPointCategoryResolver $categories,
        private readonly Group $group,
        private readonly bool $defaultPublished = false,
    ) {
        $columnsByField = [];

        foreach ($columns as $index => $column) {
            if ($column->field !== MapPointSpreadsheetField::IGNORE) {
                $columnsByField[$column->field->value] = $index;
            }
        }

        $this->columnsByField = $columnsByField;
    }

    /**
     * Only the first sheet is imported, the one the user saw when choosing the mapping.
     *
     * @return array<int, $this>
     */
    public function sheets(): array
    {
        return [0 => $this];
    }

    /**
     * Row 1 holds the headers. They are read by SpreadsheetReader instead of WithHeadingRow, because the
     * mapping works per column and the heading row formatter would turn headers into slugged array keys.
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * Rules are keyed by the column index, because every field can sit in any column of the file.
     *
     * @return array<array-key, array<int, string>>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (self::checks() as $field => $check) {
            if (isset($this->columnsByField[$field])) {
                $rules[(string) $this->columnsByField[$field]] = $check['rules'];
            }
        }

        return $rules;
    }

    /**
     * One message per column and rule, so a wrong value reads like a sentence about that column.
     *
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        $messages = [];

        foreach (self::checks() as $field => $check) {
            if (! isset($this->columnsByField[$field])) {
                continue;
            }

            foreach ($check['rules'] as $rule) {
                $name = Str::before($rule, ':');

                if ($name !== 'nullable') {
                    $messages[$this->columnsByField[$field].'.'.$name] = $check['message'];
                }
            }
        }

        return $messages;
    }

    /**
     * Failures name the column the way the file writes it.
     *
     * @return array<array-key, string>
     */
    public function customValidationAttributes(): array
    {
        $attributes = [];

        foreach ($this->columns as $index => $column) {
            $attributes[(string) $index] = $column->header;
        }

        return $attributes;
    }

    /**
     * Cells arrive as the file holds them. Spreadsheets have no types for what we need, so this turns them
     * into the values the rules and the import expect: trimmed text, coordinates written with a decimal
     * comma as numbers, and yes or no as a boolean.
     *
     * @param  array<array-key, mixed>  $row
     * @return array<array-key, mixed>
     */
    public function prepareForValidation(array $row, int $index): array
    {
        foreach ($this->columnsByField as $field => $column) {
            $value = $row[$column] ?? null;

            $row[$column] = match (MapPointSpreadsheetField::from($field)) {
                MapPointSpreadsheetField::LATITUDE, MapPointSpreadsheetField::LONGITUDE => SpreadsheetCell::decimal($value) ?? SpreadsheetCell::text($value),
                MapPointSpreadsheetField::PUBLISHED => SpreadsheetCell::boolean($value) ?? SpreadsheetCell::text($value),
                default => SpreadsheetCell::text($value),
            };
        }

        return $row;
    }

    public function onRow(Row $row): void
    {
        $cells = $row->toArray();
        $values = [];

        foreach ($this->columnsByField as $field => $column) {
            $values[$field] = $cells[$column] ?? null;
        }

        try {
            $this->importedRows[] = $this->importRow($row->getIndex(), $values);
        } catch (SpreadsheetValueException $exception) {
            $this->errors[] = new SpreadsheetRowErrorData($row->getIndex(), $this->headerOf($exception->field), $exception->getMessage());
        } catch (QueryException) {
            $this->errors[] = new SpreadsheetRowErrorData($row->getIndex(), null, 'Die Zeile konnte nicht gespeichert werden.');
        }
    }

    /**
     * Rows that fail a rule are never imported. They are reported with the column they concern.
     */
    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            foreach ($failure->errors() as $message) {
                $this->errors[] = new SpreadsheetRowErrorData($failure->row(), $failure->attribute(), $message);
            }
        }
    }

    /**
     * @return array<int, MapPointImportRowData>
     */
    public function importedRows(): array
    {
        return $this->importedRows;
    }

    /**
     * @return array<int, SpreadsheetRowErrorData>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @param  array<string, mixed>  $values  The cells of the row, keyed by the field they are mapped to.
     *
     * @throws SpreadsheetValueException When the row refers to a point or category that cannot be used.
     */
    private function importRow(int $rowNumber, array $values): MapPointImportRowData
    {
        $existingPoint = $this->matcher->find(SpreadsheetCell::text($values[$this->matcher->keyField->value] ?? null));

        $categoryIsMapped = array_key_exists(MapPointSpreadsheetField::CATEGORY->value, $values);
        $categoryName = SpreadsheetCell::text($values[MapPointSpreadsheetField::CATEGORY->value] ?? null);
        $category = $categoryName === null ? null : $this->categories->resolve($categoryName);

        $attributes = [
            'title' => $this->requiredText($values, MapPointSpreadsheetField::TITLE),
            'coordinate' => new Coordinate(
                $this->requiredDecimal($values, MapPointSpreadsheetField::LATITUDE),
                $this->requiredDecimal($values, MapPointSpreadsheetField::LONGITUDE),
            ),
            ...$this->optionalTexts($values),
            ...($categoryIsMapped ? ['category_id' => $category?->id] : []),
            ...(array_key_exists(MapPointSpreadsheetField::PUBLISHED->value, $values)
                ? ['published' => $values[MapPointSpreadsheetField::PUBLISHED->value] === true]
                : []),
        ];

        // Points are recognised by the key field, so an import never changes it.
        if ($existingPoint !== null) {
            unset($attributes[$this->matcher->keyField->value]);
        }

        // Points that already exist keep their visibility unless the file has a column for it.
        $mapPoint = $existingPoint ?? new MapPoint(['group_id' => $this->group->id, 'published' => $this->defaultPublished]);
        $mapPoint->fill($attributes)->save();

        return new MapPointImportRowData(
            row: $rowNumber,
            is_update: $existingPoint !== null,
            title: $mapPoint->title,
            lat: $mapPoint->coordinate->lat,
            lng: $mapPoint->coordinate->lng,
            location: $mapPoint->location,
            category: $categoryIsMapped ? $category?->name : $existingPoint?->category?->name,
            published: $mapPoint->published,
            group_name: $existingPoint?->group->name ?? $this->group->name,
        );
    }

    /**
     * Description and location are only changed when the file has a column for them.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, string|null>
     */
    private function optionalTexts(array $values): array
    {
        $texts = [];

        foreach ([MapPointSpreadsheetField::DESCRIPTION, MapPointSpreadsheetField::LOCATION] as $field) {
            if (array_key_exists($field->value, $values)) {
                $texts[$field->value] = SpreadsheetCell::text($values[$field->value]);
            }
        }

        return $texts;
    }

    /**
     * The rules refused rows without these values already. Reading them again keeps the types sound.
     *
     * @param  array<string, mixed>  $values
     */
    private function requiredText(array $values, MapPointSpreadsheetField $field): string
    {
        return SpreadsheetCell::text($values[$field->value] ?? null)
            ?? throw new SpreadsheetValueException('Der Wert fehlt.', $field->value);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function requiredDecimal(array $values, MapPointSpreadsheetField $field): float
    {
        return SpreadsheetCell::decimal($values[$field->value] ?? null)
            ?? throw new SpreadsheetValueException('Der Wert ist keine Zahl.', $field->value);
    }

    private function headerOf(?string $field): ?string
    {
        $column = $field === null ? null : ($this->columnsByField[$field] ?? null);

        return $column === null ? null : $this->columns[$column]->header;
    }

    /**
     * The fields whose values a row must hold, with the message shown for the column they sit in.
     *
     * @return array<string, array{rules: array<int, string>, message: string}>
     */
    private static function checks(): array
    {
        return [
            MapPointSpreadsheetField::TITLE->value => ['rules' => ['required', 'string'], 'message' => 'Der Titel fehlt.'],
            MapPointSpreadsheetField::LATITUDE->value => ['rules' => ['required', 'numeric', 'between:-90,90'], 'message' => 'Kein gültiger Breitengrad.'],
            MapPointSpreadsheetField::LONGITUDE->value => ['rules' => ['required', 'numeric', 'between:-180,180'], 'message' => 'Kein gültiger Längengrad.'],
            MapPointSpreadsheetField::PUBLISHED->value => ['rules' => ['nullable', 'boolean'], 'message' => 'Erlaubt sind „ja“ oder „nein“.'],
        ];
    }
}
