<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\FormField;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
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
    private ?Collection $pointFields = null;

    /** @var array<int, int>|null */
    private ?array $characteristicIds = null;

    /**
     * Requests with images are sent as form data, which cannot hold an empty list, so the form sends an empty string
     * (turned into null by the middleware).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('characteristic_ids') && in_array($this->input('characteristic_ids'), ['', null], true)) {
            $this->merge(['characteristic_ids' => []]);
        }
    }

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
            'characteristic_ids' => ['sometimes', 'array'],
            'characteristic_ids.*' => ['bail', 'uuid', 'distinct', 'exists:map_point_characteristics,uuid'],
        ];
    }

    /**
     * The values are checked against the fields of the submitted category and characteristics. Values of other fields are dropped.
     *
     * @return array<string, array<int, mixed>>
     */
    private function fieldValueRules(): array
    {
        $mapPoint = $this->route('mappoint');

        return app(MapPointFieldService::class)->validationRules($this->pointFields(), 'field_values', $mapPoint instanceof MapPoint ? $mapPoint : null);
    }

    /**
     * The fields the point has after saving. Loaded once, because the rules, the attribute names and storing the values need them.
     *
     * @return Collection<int, FormField>
     */
    public function pointFields(): Collection
    {
        return $this->pointFields ??= app(MapPointFieldService::class)->effectiveFields($this->submittedCategoryId(), $this->characteristicIds());
    }

    /**
     * The characteristics the point has after saving. Without submitted characteristics, the point keeps its current
     * ones. Characteristics the submitted category does not offer are dropped, e.g. after changing the category.
     *
     * @return array<int, int>
     */
    public function characteristicIds(): array
    {
        if ($this->characteristicIds !== null) {
            return $this->characteristicIds;
        }

        $mapPoint = $this->route('mappoint');
        $submittedUuids = array_filter((array) $this->input('characteristic_ids', []), fn (mixed $uuid): bool => is_string($uuid) && Str::isUuid($uuid));

        $characteristics = $this->has('characteristic_ids')
            ? MapPointCharacteristic::whereIn('uuid', $submittedUuids)->get()
            : ($mapPoint instanceof MapPoint ? $mapPoint->characteristics()->get() : new Collection);

        $categoryId = $this->submittedCategoryId();
        $tree = MapPointCategory::tree();

        return $this->characteristicIds = $characteristics
            ->filter(fn (MapPointCharacteristic $characteristic): bool => $characteristic->isSelectableFor($categoryId, $tree))
            ->modelKeys();
    }

    private function submittedCategoryId(): ?int
    {
        $categoryUuid = $this->input('category_id');

        return is_string($categoryUuid) && Str::isUuid($categoryUuid) ? MapPointCategory::where('uuid', $categoryUuid)->value('id') : null;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $fieldLabels = $this->pointFields()
            ->mapWithKeys(fn (FormField $field): array => ['field_values.'.$field->uuid => $field->label])
            ->all();

        return [
            ...$fieldLabels,
            'group_id' => 'Initiative',
            'category_id' => 'Kategorie',
            'characteristic_ids' => 'Maßnahmen',
        ];
    }

    /**
     * Values of the point's fields keyed by field uuid. Missing when the client did not send any, so stored values are kept.
     *
     * @return array<string, mixed>
     */
    public function fieldValues(): array
    {
        return $this->validated('field_values') ?? [];
    }

    /**
     * The point may only be assigned to a group the user administers, its category must be usable
     * in that group and its characteristics must be offered by the category.
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
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['category_id', 'characteristic_ids', 'characteristic_ids.*']) || ! $this->has('characteristic_ids')) {
                    return;
                }

                if (count($this->characteristicIds()) !== count((array) $this->input('characteristic_ids'))) {
                    $validator->errors()->add('characteristic_ids', 'Diese Maßnahmen sind für die gewählte Kategorie nicht verfügbar.');
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
            ...$this->safe()->except(['group_id', 'category_id', 'field_values', 'characteristic_ids']),
            'group_id' => Group::where('uuid', $this->validated('group_id'))->value('id'),
            'category_id' => MapPointCategory::where('uuid', $this->validated('category_id'))->value('id'),
        ];
    }
}
