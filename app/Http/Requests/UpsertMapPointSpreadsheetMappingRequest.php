<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Context\GroupContextContract;
use App\Enums\MapPointSpreadsheetField;
use App\Models\Group;
use App\Models\MapPointSpreadsheetMapping;
use App\Rules\DistinctMapPointSpreadsheetFields;
use App\Rules\IdColumnRequiresIdKey;
use App\Rules\UsableCategoryFieldColumns;
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
        $groupId = $this->group()?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('map_point_spreadsheet_mappings', 'name')->where('group_id', $groupId)->ignore($mapping?->id),
            ],
            'key_field' => ['required', Rule::enum(MapPointSpreadsheetField::class)->only(MapPointSpreadsheetField::keyOptions()), new IdColumnRequiresIdKey($this->input('columns'))],
            'columns' => ['required', 'array', 'min:1', new DistinctMapPointSpreadsheetFields, new UsableCategoryFieldColumns($this->group())],
            'columns.*.header' => ['required', 'string', 'max:255'],
            // A new field would be created again on every import with the template.
            'columns.*.field' => ['required', Rule::enum(MapPointSpreadsheetField::class)->except([MapPointSpreadsheetField::NEW_CATEGORY_FIELD])],
            'columns.*.form_field_id' => ['nullable', 'required_if:columns.*.field,'.MapPointSpreadsheetField::FIELD->value, 'uuid'],
            'columns.*.characteristic_id' => ['nullable', 'required_if:columns.*.field,'.MapPointSpreadsheetField::CHARACTERISTIC->value, 'uuid'],
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
     * Templates belong to the group they were saved in, new ones to the current group.
     */
    private function group(): ?Group
    {
        $mapping = $this->route('mapping');

        return $mapping instanceof MapPointSpreadsheetMapping ? $mapping->group : app(GroupContextContract::class)->getCurrentGroup();
    }

    /**
     * @return array{name: string, key_field: MapPointSpreadsheetField, columns: array<int, array{header: string, field: string, form_field_id?: string, characteristic_id?: string}>}
     */
    public function mappingData(): array
    {
        $columns = $this->validated('columns');

        return [
            'name' => $this->string('name')->toString(),
            'key_field' => MapPointSpreadsheetField::from($this->string('key_field')->toString()),
            'columns' => array_values(array_map(
                fn (array $column): array => [
                    'header' => (string) $column['header'],
                    'field' => (string) $column['field'],
                    ...(isset($column['form_field_id']) ? ['form_field_id' => (string) $column['form_field_id']] : []),
                    ...(isset($column['characteristic_id']) ? ['characteristic_id' => (string) $column['characteristic_id']] : []),
                ],
                is_array($columns) ? $columns : [],
            )),
        ];
    }
}
