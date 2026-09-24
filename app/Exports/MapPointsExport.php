<?php

declare(strict_types=1);

namespace App\Exports;

use App\Data\MapPointSpreadsheetColumnData;
use App\Enums\MapPointSpreadsheetField;
use App\Enums\SpreadsheetFormat;
use App\Imports\SpreadsheetCell;
use App\Models\MapPoint;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\DefaultValueBinder;
use Override;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * Exports map points with the columns of an import mapping, so the file can be edited and imported again.
 * The ID column always comes first, it lets the import update the exported points.
 *
 * Titles and descriptions are written by many people, so no text may end up as a formula that runs
 * when the file is opened.
 */
class MapPointsExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomCsvSettings, WithCustomValueBinder, WithHeadings, WithMapping
{
    /** @var array<int, MapPointSpreadsheetColumnData> */
    private readonly array $exportColumns;

    /**
     * @param  Collection<int, MapPoint>  $mapPoints
     * @param  array<int, MapPointSpreadsheetColumnData>  $columns
     */
    public function __construct(private readonly Collection $mapPoints, array $columns, private readonly SpreadsheetFormat $format)
    {
        $idColumn = collect($columns)->first(fn (MapPointSpreadsheetColumnData $column): bool => $column->field === MapPointSpreadsheetField::ID)
            ?? new MapPointSpreadsheetColumnData('ID', MapPointSpreadsheetField::ID);

        $otherColumns = array_filter(
            $columns,
            fn (MapPointSpreadsheetColumnData $column): bool => ! in_array($column->field, [MapPointSpreadsheetField::ID, MapPointSpreadsheetField::IGNORE], true),
        );

        $this->exportColumns = [$idColumn, ...array_values($otherColumns)];
    }

    /**
     * All fields with their labels as headers, used when no mapping is chosen.
     *
     * @return array<int, MapPointSpreadsheetColumnData>
     */
    public static function defaultColumns(): array
    {
        $fields = array_filter(MapPointSpreadsheetField::cases(), fn (MapPointSpreadsheetField $field): bool => $field !== MapPointSpreadsheetField::IGNORE);

        return array_values(array_map(
            fn (MapPointSpreadsheetField $field): MapPointSpreadsheetColumnData => new MapPointSpreadsheetColumnData($field->label(), $field),
            $fields,
        ));
    }

    /**
     * @return Collection<int, MapPoint>
     */
    public function collection(): Collection
    {
        return $this->mapPoints;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return array_map(fn (MapPointSpreadsheetColumnData $column): string => $column->header, $this->exportColumns);
    }

    /**
     * @param  MapPoint  $row
     * @return array<int, string|float|null>
     */
    public function map($row): array
    {
        return array_map(fn (MapPointSpreadsheetColumnData $column): string|float|null => match ($column->field) {
            MapPointSpreadsheetField::ID => $row->uuid,
            MapPointSpreadsheetField::TITLE => $this->text($row->title),
            MapPointSpreadsheetField::DESCRIPTION => $this->text($row->description),
            MapPointSpreadsheetField::LATITUDE => $row->coordinate->lat,
            MapPointSpreadsheetField::LONGITUDE => $row->coordinate->lng,
            MapPointSpreadsheetField::LOCATION => $this->text($row->location),
            MapPointSpreadsheetField::CATEGORY => $this->text($row->category?->name),
            MapPointSpreadsheetField::PUBLISHED => $row->published ? 'ja' : 'nein',
            MapPointSpreadsheetField::IGNORE => null,
        }, $this->exportColumns);
    }

    /**
     * Text is always stored as text in Excel and OpenDocument files. The default binder would turn text
     * starting with "=" into a formula and numeric text like a postal code into a number.
     */
    #[Override]
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    /**
     * CSV files have no cell types, so text that would run as a formula is escaped there instead.
     */
    private function text(?string $text): ?string
    {
        return $text !== null && $this->format === SpreadsheetFormat::CSV ? SpreadsheetCell::escapeFormula($text) : $text;
    }

    /**
     * Semicolons and a byte order mark let German Excel open the file with correct columns and umlauts.
     *
     * @return array<string, mixed>
     */
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
            'use_bom' => true,
        ];
    }
}
