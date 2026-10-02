<?php

declare(strict_types=1);

namespace App\Exports;

use App\Data\MapPointSpreadsheetColumnData;
use App\Data\MapPointSpreadsheetFieldData;
use App\Enums\MapPointSpreadsheetField;
use App\Enums\SpreadsheetFormat;
use App\Imports\MapPointFieldCell;
use App\Imports\SpreadsheetCell;
use App\Models\FormField;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use App\Models\MapPointField;
use App\Services\MapPointFieldService;
use App\ValueObjects\MapPointCategoryTree;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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

    private readonly MapPointCategoryTree $tree;

    private readonly MapPointFieldService $fieldService;

    /**
     * @param  Collection<int, MapPoint>  $mapPoints  With the relations characteristics, fields.formField and fields.options loaded.
     * @param  array<int, MapPointSpreadsheetColumnData>  $columns
     * @param  EloquentCollection<int, FormField>  $usableFields  The fields that can be exported. Columns of other fields, e.g. deleted ones, are left out.
     * @param  EloquentCollection<int, MapPointCharacteristic>  $usableCharacteristics  The characteristics that can be exported, only those of
     *                                                                                  one category. Columns of others are left out.
     */
    public function __construct(
        private readonly Collection $mapPoints,
        array $columns,
        private readonly SpreadsheetFormat $format,
        EloquentCollection $usableFields = new EloquentCollection,
        ?MapPointCategoryTree $tree = null,
        ?MapPointFieldService $fieldService = null,
        EloquentCollection $usableCharacteristics = new EloquentCollection,
    ) {
        $this->tree = $tree ?? MapPointCategory::tree();
        $this->fieldService = $fieldService ?? app(MapPointFieldService::class);

        $idColumn = collect($columns)->first(fn (MapPointSpreadsheetColumnData $column): bool => $column->field === MapPointSpreadsheetField::ID)
            ?? new MapPointSpreadsheetColumnData('ID', MapPointSpreadsheetField::ID);

        $usableFieldIds = $usableFields->pluck('uuid')->all();
        $usableCharacteristicIds = $usableCharacteristics->pluck('uuid')->all();

        $otherColumns = array_filter(
            $columns,
            fn (MapPointSpreadsheetColumnData $column): bool => match ($column->field) {
                MapPointSpreadsheetField::ID, MapPointSpreadsheetField::IGNORE, MapPointSpreadsheetField::NEW_CATEGORY_FIELD => false,
                MapPointSpreadsheetField::FIELD => in_array($column->form_field_id, $usableFieldIds, true),
                MapPointSpreadsheetField::CHARACTERISTIC => in_array($column->characteristic_id, $usableCharacteristicIds, true),
                default => true,
            },
        );

        $this->exportColumns = [$idColumn, ...array_values($otherColumns)];
    }

    /**
     * All fields with their labels as headers, used when no mapping is chosen. Fields are named after their category
     * and characteristic, because they may have fields with the same label. Each characteristic comes right before its fields.
     *
     * @param  EloquentCollection<int, FormField>  $fields  The fields of categories and characteristics. Fields of characteristics need the relation formDefinition.mapPointCharacteristic.
     * @param  EloquentCollection<int, MapPointCharacteristic>  $characteristics
     * @return array<int, MapPointSpreadsheetColumnData>
     */
    public static function defaultColumns(EloquentCollection $fields = new EloquentCollection, EloquentCollection $characteristics = new EloquentCollection): array
    {
        $fieldColumn = fn (FormField $field): MapPointSpreadsheetColumnData => new MapPointSpreadsheetColumnData(
            MapPointSpreadsheetFieldData::fieldLabel($field),
            MapPointSpreadsheetField::FIELD,
            $field->uuid,
        );
        [$characteristicFields, $categoryFields] = $fields->partition(fn (FormField $field): bool => $field->formDefinition?->mapPointCharacteristic !== null);

        $fields = array_filter(MapPointSpreadsheetField::pointFields(), fn (MapPointSpreadsheetField $field): bool => $field !== MapPointSpreadsheetField::IGNORE);

        return [
            ...array_values(array_map(
                fn (MapPointSpreadsheetField $field): MapPointSpreadsheetColumnData => new MapPointSpreadsheetColumnData($field->label(), $field),
                $fields,
            )),
            ...$categoryFields->map($fieldColumn)->all(),
            ...$characteristics->flatMap(fn (MapPointCharacteristic $characteristic): array => [
                new MapPointSpreadsheetColumnData(
                    MapPointSpreadsheetFieldData::characteristicLabel($characteristic),
                    MapPointSpreadsheetField::CHARACTERISTIC,
                    characteristic_id: $characteristic->uuid,
                ),
                ...$characteristicFields
                    ->filter(fn (FormField $field): bool => $field->form_definition_id === $characteristic->form_definition_id)
                    ->map($fieldColumn)
                    ->all(),
            ])->all(),
        ];
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
     * @return array<int, string|int|float|null>
     */
    public function map($row): array
    {
        return array_map(fn (MapPointSpreadsheetColumnData $column): string|int|float|null => match ($column->field) {
            MapPointSpreadsheetField::ID => $row->uuid,
            MapPointSpreadsheetField::TITLE => $this->text($row->title),
            MapPointSpreadsheetField::DESCRIPTION => $this->text($row->description),
            MapPointSpreadsheetField::LATITUDE => $row->coordinate->lat,
            MapPointSpreadsheetField::LONGITUDE => $row->coordinate->lng,
            MapPointSpreadsheetField::LOCATION => $this->text($row->location),
            MapPointSpreadsheetField::CATEGORY => $this->text($row->category?->name),
            MapPointSpreadsheetField::PUBLISHED => $row->published ? 'ja' : 'nein',
            MapPointSpreadsheetField::FIELD => $this->fieldValue($row, (string) $column->form_field_id),
            MapPointSpreadsheetField::CHARACTERISTIC => $row->characteristics->contains('uuid', $column->characteristic_id) ? 1 : 0,
            MapPointSpreadsheetField::IGNORE, MapPointSpreadsheetField::NEW_CATEGORY_FIELD => null,
        }, $this->exportColumns);
    }

    /**
     * Only values of the point's current fields are exported. Former values would be dropped by an import anyway.
     */
    private function fieldValue(MapPoint $mapPoint, string $formFieldId): string|int|float|null
    {
        $field = $this->fieldService->activeFields($mapPoint, $this->tree)->first(fn (MapPointField $field): bool => $field->formField?->uuid === $formFieldId);
        $value = $field === null ? null : MapPointFieldCell::export($field);

        return is_string($value) ? $this->text($value) : $value;
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
