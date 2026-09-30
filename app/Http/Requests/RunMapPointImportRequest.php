<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Context\GroupContextContract;
use App\Data\MapPointSpreadsheetColumnData;
use App\Enums\FieldType;
use App\Enums\MapPointSpreadsheetField;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Rules\DistinctMapPointSpreadsheetFields;
use App\Rules\IdColumnRequiresIdKey;
use App\Rules\UsableCategoryFieldColumns;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
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
            'columns' => ['required', 'array', 'min:1', new DistinctMapPointSpreadsheetFields, new UsableCategoryFieldColumns($this->group())],
            'columns.*.header' => ['required', 'string'],
            'columns.*.field' => ['required', Rule::enum(MapPointSpreadsheetField::class)],
            'columns.*.category_field_id' => ['nullable', 'required_if:columns.*.field,'.MapPointSpreadsheetField::CATEGORY_FIELD->value, 'uuid'],
            'columns.*.new_field_type' => [
                'nullable',
                'required_if:columns.*.field,'.MapPointSpreadsheetField::NEW_CATEGORY_FIELD->value,
                Rule::enum(FieldType::class)->only(MapPointSpreadsheetField::newFieldTypes()),
            ],
            'main_category_id' => ['nullable', 'bail', 'uuid', 'exists:map_point_categories,uuid'],
            'new_main_category_name' => ['nullable', 'string', 'max:255', 'prohibits:main_category_id'],
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
            'main_category_id' => 'Hauptkategorie',
            'new_main_category_name' => 'Name der neuen Hauptkategorie',
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
            function (Validator $validator): void {
                if ($validator->errors()->isEmpty()) {
                    $this->validateMainCategory($validator);
                }
            },
        ];
    }

    /**
     * The main category must be usable in the group, and new fields need one. They are created where the fields
     * of the main category live, which may be a category of a parent initiative that only its admins may change.
     */
    private function validateMainCategory(Validator $validator): void
    {
        $group = $this->group();
        $mainCategory = $this->mainCategory();
        $newName = $this->newMainCategoryName();
        $createsFields = collect($this->columnsFromInput())->contains(fn (MapPointSpreadsheetColumnData $column): bool => $column->field === MapPointSpreadsheetField::NEW_CATEGORY_FIELD);

        if ($group === null) {
            return;
        }

        if ($mainCategory !== null && ! $mainCategory->isUsableInGroup($group)) {
            $validator->errors()->add('main_category_id', 'Diese Kategorie ist für die Initiative nicht verfügbar.');

            return;
        }

        if ($newName !== null && MapPointCategory::usableInGroup($group)->get()->contains(fn (MapPointCategory $category): bool => Str::lower($category->name) === Str::lower($newName))) {
            $validator->errors()->add('new_main_category_name', 'Diese Kategorie gibt es schon. Wähle sie als Hauptkategorie aus.');

            return;
        }

        if ($createsFields && $mainCategory === null && $newName === null) {
            $validator->errors()->add('columns', 'Neue Zusatzfelder werden in der Hauptkategorie angelegt. Bitte wähle zuerst eine Hauptkategorie.');

            return;
        }

        if ($createsFields && $mainCategory !== null) {
            $fieldsCategory = MapPointCategory::findOrFail(MapPointCategory::tree()->fieldsCategoryId($mainCategory->id) ?? $mainCategory->id);

            if (! $this->user()->can('update', $fieldsCategory)) {
                $validator->errors()->add('columns', "Neue Zusatzfelder kämen in die Kategorie {$fieldsCategory->name} einer übergeordneten Initiative. Dort kann nur deren Admin Felder anlegen.");
            }
        }
    }

    public function mainCategory(): ?MapPointCategory
    {
        $uuid = $this->input('main_category_id');

        return is_string($uuid) ? MapPointCategory::where('uuid', $uuid)->first() : null;
    }

    public function newMainCategoryName(): ?string
    {
        $name = trim($this->string('new_main_category_name')->toString());

        return $name === '' ? null : $name;
    }

    /**
     * Points are imported into the current group, so its categories decide which fields columns may hold.
     */
    private function group(): ?Group
    {
        return app(GroupContextContract::class)->getCurrentGroup();
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
                category_field_id: isset($column['category_field_id']) ? (string) $column['category_field_id'] : null,
                new_field_type: isset($column['new_field_type']) ? FieldType::tryFrom((string) $column['new_field_type']) : null,
            ),
            $columns,
        ));
    }
}
