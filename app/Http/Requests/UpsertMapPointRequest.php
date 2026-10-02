<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\FormField;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Rules\GeographicCoordinate;
use App\Services\MapPointFieldService;
use App\Services\MapPointVisibilityService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class UpsertMapPointRequest extends FormRequest
{
    /** @var Collection<int, FormField>|null */
    private ?Collection $submittedCategoryFields = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $mapPoint = $this->route('mappoint');

        return $mapPoint instanceof MapPoint
            ? $this->user()->can('update', $mapPoint)
            : $this->user()->can('create', MapPoint::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->fieldValueRules(),
            'title' => ['required'],
            'description' => ['nullable'],
            'coordinate' => new GeographicCoordinate,
            'published' => ['boolean'],
            'group_id' => ['required', 'bail', 'uuid', 'exists:groups,uuid'],
            'category_id' => ['nullable', 'bail', 'uuid', 'exists:map_point_categories,uuid'],
            'location' => ['nullable', 'string', 'max:500'],
            'field_values' => ['sometimes', 'array'],
        ];
    }

    /**
     * The values are checked against the fields of the submitted category. Values of other fields are dropped.
     *
     * @return array<string, array<int, mixed>>
     */
    private function fieldValueRules(): array
    {
        $mapPoint = $this->route('mappoint');

        return app(MapPointFieldService::class)->validationRules($this->submittedCategoryFields(), 'field_values', $mapPoint instanceof MapPoint ? $mapPoint : null);
    }

    /**
     * Loaded once, because both the rules and the attribute names need them.
     *
     * @return Collection<int, FormField>
     */
    private function submittedCategoryFields(): Collection
    {
        if ($this->submittedCategoryFields !== null) {
            return $this->submittedCategoryFields;
        }

        $categoryUuid = $this->input('category_id');
        $categoryId = is_string($categoryUuid) && Str::isUuid($categoryUuid) ? MapPointCategory::where('uuid', $categoryUuid)->value('id') : null;

        return $this->submittedCategoryFields = app(MapPointFieldService::class)->fieldsOfCategory($categoryId);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $fieldLabels = $this->submittedCategoryFields()
            ->mapWithKeys(fn (FormField $field): array => ['field_values.'.$field->uuid => $field->label])
            ->all();

        return [
            ...$fieldLabels,
            'group_id' => 'Initiative',
            'category_id' => 'Kategorie',
        ];
    }

    /**
     * Values of the category fields keyed by field uuid. Missing when the client did not send any, so stored values are kept.
     *
     * @return array<string, mixed>
     */
    public function fieldValues(): array
    {
        return $this->validated('field_values') ?? [];
    }

    /**
     * The point may only be assigned to a group the user administers, and its category
     * must be usable in that group.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['group_id', 'category_id'])) {
                    return;
                }

                $group = Group::where('uuid', $this->input('group_id'))->firstOrFail();

                if (! app(MapPointVisibilityService::class)->isSelectableGroup($this->user(), $group)) {
                    $validator->errors()->add('group_id', 'Du darfst dieser Initiative keine Kartenpunkte zuordnen.');

                    return;
                }

                $categoryUuid = $this->input('category_id');

                if ($categoryUuid === null) {
                    return;
                }

                $category = MapPointCategory::where('uuid', $categoryUuid)->firstOrFail();

                if (! $category->isUsableInGroup($group)) {
                    $validator->errors()->add('category_id', 'Diese Kategorie ist für die gewählte Initiative nicht verfügbar.');
                }
            },
        ];
    }

    /**
     * Group and category are submitted as uuids and translated into foreign keys here.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return [
            ...$this->safe()->except(['group_id', 'category_id', 'field_values']),
            'group_id' => Group::where('uuid', $this->validated('group_id'))->value('id'),
            'category_id' => MapPointCategory::where('uuid', $this->validated('category_id'))->value('id'),
        ];
    }
}
