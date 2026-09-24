<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\MapPointSpreadsheetColumnData;
use App\Enums\MapPointSpreadsheetField;
use App\Models\MapPoint;
use App\Rules\DistinctMapPointSpreadsheetFields;
use App\Rules\IdColumnRequiresIdKey;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RunMapPointImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('import', MapPoint::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'uuid'],
            'key_field' => ['required', Rule::enum(MapPointSpreadsheetField::class)->only(MapPointSpreadsheetField::keyOptions()), new IdColumnRequiresIdKey($this->input('columns'))],
            'default_published' => ['required', 'boolean'],
            'columns' => ['required', 'array', 'min:1', new DistinctMapPointSpreadsheetFields],
            'columns.*.header' => ['required', 'string'],
            'columns.*.field' => ['required', Rule::enum(MapPointSpreadsheetField::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'key_field' => 'Erkennung vorhandener Punkte',
            'default_published' => 'Sichtbarkeit neuer Punkte',
            'columns' => 'Spalten',
        ];
    }

    /**
     * Title and coordinates are needed to create points, the key field to find existing ones.
     * Without a key field every row is created, so only the fields for new points are required.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $mappedFields = array_map(
                    fn (MapPointSpreadsheetColumnData $column): string => $column->field->value,
                    $this->columnsFromInput(),
                );

                $requiredFields = [
                    MapPointSpreadsheetField::TITLE,
                    MapPointSpreadsheetField::LATITUDE,
                    MapPointSpreadsheetField::LONGITUDE,
                    ...($this->keyField() === MapPointSpreadsheetField::IGNORE ? [] : [$this->keyField()]),
                ];

                $missingLabels = collect($requiredFields)
                    ->filter(fn (MapPointSpreadsheetField $field): bool => ! in_array($field->value, $mappedFields, true))
                    ->map(fn (MapPointSpreadsheetField $field): string => $field->label())
                    ->unique();

                if ($missingLabels->isNotEmpty()) {
                    $validator->errors()->add('columns', 'Diese Felder müssen einer Spalte zugeordnet sein: '.$missingLabels->implode(', ').'.');
                }
            },
        ];
    }

    public function token(): string
    {
        return $this->string('token')->toString();
    }

    public function keyField(): MapPointSpreadsheetField
    {
        return MapPointSpreadsheetField::from($this->string('key_field')->toString());
    }

    /**
     * Whether points the import creates are public. A column mapped to the published field decides per row instead.
     */
    public function defaultPublished(): bool
    {
        return $this->boolean('default_published');
    }

    /**
     * @return array<int, MapPointSpreadsheetColumnData>
     */
    public function columns(): array
    {
        return $this->columnsFromInput();
    }

    /**
     * @return array<int, MapPointSpreadsheetColumnData>
     */
    private function columnsFromInput(): array
    {
        $columns = $this->input('columns');

        if (! is_array($columns)) {
            return [];
        }

        return array_values(array_map(
            fn (array $column): MapPointSpreadsheetColumnData => new MapPointSpreadsheetColumnData(
                header: (string) $column['header'],
                field: MapPointSpreadsheetField::from((string) $column['field']),
            ),
            $columns,
        ));
    }
}
