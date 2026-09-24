<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Context\GroupContextContract;
use App\Enums\MapPointSpreadsheetField;
use App\Models\MapPointSpreadsheetMapping;
use App\Rules\DistinctMapPointSpreadsheetFields;
use App\Rules\IdColumnRequiresIdKey;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertMapPointSpreadsheetMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mapping = $this->route('mapping');

        return $mapping instanceof MapPointSpreadsheetMapping
            ? $this->user()->can('update', $mapping)
            : $this->user()->can('create', MapPointSpreadsheetMapping::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $mapping = $this->route('mapping');
        $mapping = $mapping instanceof MapPointSpreadsheetMapping ? $mapping : null;
        $groupId = $mapping->group_id ?? app(GroupContextContract::class)->getCurrentGroup()?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('map_point_spreadsheet_mappings', 'name')->where('group_id', $groupId)->ignore($mapping?->id),
            ],
            'key_field' => ['required', Rule::enum(MapPointSpreadsheetField::class)->only(MapPointSpreadsheetField::keyOptions()), new IdColumnRequiresIdKey($this->input('columns'))],
            'columns' => ['required', 'array', 'min:1', new DistinctMapPointSpreadsheetFields],
            'columns.*.header' => ['required', 'string', 'max:255'],
            'columns.*.field' => ['required', Rule::enum(MapPointSpreadsheetField::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'key_field' => 'Erkennung vorhandener Punkte',
            'columns' => 'Spalten',
        ];
    }

    /**
     * @return array{name: string, key_field: MapPointSpreadsheetField, columns: array<int, array{header: string, field: string}>}
     */
    public function mappingData(): array
    {
        $columns = $this->validated('columns');

        return [
            'name' => $this->string('name')->toString(),
            'key_field' => MapPointSpreadsheetField::from($this->string('key_field')->toString()),
            'columns' => array_values(array_map(
                fn (array $column): array => ['header' => (string) $column['header'], 'field' => (string) $column['field']],
                is_array($columns) ? $columns : [],
            )),
        ];
    }
}
