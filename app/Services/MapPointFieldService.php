<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FieldType;
use App\Models\FormField;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use App\Models\MapPointField;
use App\ValueObjects\MapPointCategoryTree;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Stores the values of map point fields on points. The fields of a point come from its category, or from the
 * nearest parent category with fields, plus the fields of its characteristics. A value is active while its field
 * is one of these fields. Otherwise it is a former value: it is kept, but neither shown publicly nor editable.
 * Changing the category or the characteristics back makes it active again.
 */
class MapPointFieldService
{
    /**
     * The form definitions of all characteristics with fields, keyed by characteristic id. Loaded once.
     *
     * @var array<int, int>|null
     */
    private ?array $characteristicFormDefinitionIds = null;

    /**
     * Fields per combination of form definitions, so forms and imports do not load them for every point.
     *
     * @var array<string, Collection<int, FormField>>
     */
    private array $fieldsByFormDefinitionIds = [];

    /**
     * The fields the points of the category have, in field order.
     *
     * @return Collection<int, FormField>
     */
    public function fieldsOfCategory(?int $categoryId, ?MapPointCategoryTree $tree = null): Collection
    {
        $formDefinitionId = $categoryId === null ? null : ($tree ?? MapPointCategory::tree())->fieldsFormDefinitionId($categoryId);

        if ($formDefinitionId === null) {
            return new Collection;
        }

        return FormField::where('form_definition_id', $formDefinitionId)->with('options')->orderBy('sort_order')->get();
    }

    /**
     * The fields points of the category with the given characteristics have: the category fields first, then
     * the fields of each characteristic, in field order. The point does not have to exist yet.
     *
     * @param  array<int, int>  $characteristicIds
     * @return Collection<int, FormField>
     */
    public function effectiveFields(?int $categoryId, array $characteristicIds, ?MapPointCategoryTree $tree = null): Collection
    {
        $formDefinitionIds = $this->formDefinitionIds($categoryId, $characteristicIds, $tree);
        $key = implode(',', $formDefinitionIds);

        if (! array_key_exists($key, $this->fieldsByFormDefinitionIds)) {
            $fields = FormField::whereIn('form_definition_id', $formDefinitionIds)->with('options')->get();
            $this->fieldsByFormDefinitionIds[$key] = $this->sortByFormDefinitions($fields, $formDefinitionIds);
        }

        return $this->fieldsByFormDefinitionIds[$key];
    }

    /**
     * The form definitions holding the fields of points of the category with the given characteristics,
     * the one of the category first.
     *
     * @param  array<int, int>  $characteristicIds
     * @return array<int, int>
     */
    public function formDefinitionIds(?int $categoryId, array $characteristicIds, ?MapPointCategoryTree $tree = null): array
    {
        $this->characteristicFormDefinitionIds ??= MapPointCharacteristic::query()
            ->whereNotNull('form_definition_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('form_definition_id', 'id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $categoryFormDefinitionId = $categoryId === null ? null : ($tree ?? MapPointCategory::tree())->fieldsFormDefinitionId($categoryId);
        $characteristicFormDefinitionIds = array_values(array_intersect_key($this->characteristicFormDefinitionIds, array_flip($characteristicIds)));

        return array_values(array_unique(array_filter([$categoryFormDefinitionId, ...$characteristicFormDefinitionIds])));
    }

    /**
     * The fields of each category keyed by the category's uuid, loaded with one query for all categories.
     *
     * @param  Collection<int, MapPointCategory>  $categories
     * @return array<string, Collection<int, FormField>>
     */
    public function fieldsByCategory(Collection $categories, ?MapPointCategoryTree $tree = null): array
    {
        $tree ??= MapPointCategory::tree();
        $formDefinitionIds = $categories->mapWithKeys(fn (MapPointCategory $category): array => [$category->uuid => $tree->fieldsFormDefinitionId($category->id)]);
        $fieldsByFormDefinition = FormField::whereIn('form_definition_id', $formDefinitionIds->filter())
            ->with('options')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('form_definition_id');

        return $formDefinitionIds
            ->map(fn (?int $formDefinitionId): Collection => $fieldsByFormDefinition->get($formDefinitionId) ?? new Collection)
            ->all();
    }

    /**
     * The fields of all categories the group can use, for spreadsheet columns. Sorted by category, then by field.
     *
     * @return Collection<int, FormField>
     */
    public function fieldsUsableInGroup(Group $group): Collection
    {
        return FormField::query()
            ->whereHas('formDefinition.mapPointCategory', fn (Builder $query) => $query->usableInGroup($group))
            ->with(['options', 'formDefinition.mapPointCategory'])
            ->get()
            ->sortBy([
                fn (FormField $a, FormField $b): int => strcmp((string) $a->formDefinition?->mapPointCategory?->name, (string) $b->formDefinition?->mapPointCategory?->name),
                fn (FormField $a, FormField $b): int => $a->sort_order <=> $b->sort_order,
            ])
            ->values();
    }

    /**
     * Stores the given values, keyed by the uuid of the field. Only the stored value of a field that is given
     * and belongs to the point's category or characteristics is touched. Values of other fields, including former values, are
     * kept. An empty value removes the stored value. A changed value gets a fresh snapshot of the field,
     * because it was entered against the field as it is now.
     *
     * @param  array<string, mixed>  $valuesByFieldUuid
     * @param  Collection<int, FormField>|null  $fields  The fields of the point. Pass them when storing many points.
     */
    public function syncValues(MapPoint $mapPoint, array $valuesByFieldUuid, ?Collection $fields = null): void
    {
        $fields ??= $this->effectiveFields($mapPoint->category_id, $mapPoint->characteristics()->pluck('map_point_characteristics.id')->all());
        $storedFields = $mapPoint->fields()->whereIn('form_field_id', $fields->modelKeys())->get()->keyBy('form_field_id');

        DB::transaction(function () use ($mapPoint, $valuesByFieldUuid, $fields, $storedFields): void {
            foreach ($fields as $field) {
                if (! array_key_exists($field->uuid, $valuesByFieldUuid)) {
                    continue;
                }

                $value = $this->normalize($field, $valuesByFieldUuid[$field->uuid]);
                $storedField = $storedFields->get($field->id);

                if ($storedField !== null && $this->isSameValue($field, $storedField->value, $value)) {
                    continue;
                }

                $storedField?->delete();

                if ($value !== null) {
                    $field->createMapPointField($mapPoint, $value);
                }
            }
        });

        $mapPoint->unsetRelation('fields');
    }

    /**
     * The stored values of the point's current fields: the category fields first, then the fields of each
     * characteristic, in field order. Needs the relations fields.formField and characteristics.
     *
     * @return Collection<int, MapPointField>
     */
    public function activeFields(MapPoint $mapPoint, MapPointCategoryTree $tree): Collection
    {
        $formDefinitionIds = array_values(array_unique(array_filter([
            $mapPoint->category_id === null ? null : $tree->fieldsFormDefinitionId($mapPoint->category_id),
            ...$mapPoint->characteristics->pluck('form_definition_id')->all(),
        ])));

        return $mapPoint->fields
            ->filter(fn (MapPointField $field): bool => in_array($field->formField?->form_definition_id, $formDefinitionIds, true))
            ->sortBy([
                fn (MapPointField $a, MapPointField $b): int => array_search($a->formField?->form_definition_id, $formDefinitionIds, true) <=> array_search($b->formField?->form_definition_id, $formDefinitionIds, true),
                fn (MapPointField $a, MapPointField $b): int => ($a->formField->sort_order ?? 0) <=> ($b->formField->sort_order ?? 0),
            ])
            ->values();
    }

    /**
     * Values of fields the point does not have any more, e.g. after changing its category.
     *
     * @return Collection<int, MapPointField>
     */
    public function formerFields(MapPoint $mapPoint, MapPointCategoryTree $tree): Collection
    {
        $activeIds = $this->activeFields($mapPoint, $tree)->modelKeys();

        return $mapPoint->fields
            ->reject(fn (MapPointField $field): bool => in_array($field->id, $activeIds, true))
            ->sortBy('sort_order')
            ->values();
    }

    /**
     * Drops the values that are not valid for their field, so the other values can still be stored.
     *
     * @param  Collection<int, FormField>  $fields
     * @param  array<string, mixed>  $valuesByFieldUuid
     * @return array<string, mixed>
     */
    public function validValues(Collection $fields, array $valuesByFieldUuid): array
    {
        $validValues = [];

        foreach ($fields as $field) {
            if (! array_key_exists($field->uuid, $valuesByFieldUuid)) {
                continue;
            }

            $data = ['values' => [$field->uuid => $valuesByFieldUuid[$field->uuid]]];

            if (Validator::make($data, $this->validationRules(new Collection([$field]), 'values'))->passes()) {
                $validValues[$field->uuid] = $valuesByFieldUuid[$field->uuid];
            }
        }

        return $validValues;
    }

    /**
     * Validation rules for the values of the given fields, keyed by "$prefix.$uuid". All fields are optional.
     *
     * @param  Collection<int, FormField>  $fields
     * @return array<string, array<int, mixed>>
     */
    public function validationRules(Collection $fields, string $prefix): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $options = $field->options->pluck('value')->all();

            $fieldRules = match ($field->type) {
                FieldType::SELECT => ['nullable', 'string', Rule::in($options)],
                FieldType::CHECKBOX => ['nullable', 'array'],
                default => array_values(array_diff($field->getValidationRules()[$field->uuid] ?? [], ['required'])),
            };

            if (! in_array('nullable', $fieldRules, true)) {
                array_unshift($fieldRules, 'nullable');
            }

            $rules[$prefix.'.'.$field->uuid] = $fieldRules;

            if ($field->type === FieldType::CHECKBOX) {
                $rules[$prefix.'.'.$field->uuid.'.*'] = ['string', Rule::in($options)];
            }
        }

        return $rules;
    }

    /**
     * @param  Collection<int, FormField>  $fields
     * @param  array<int, int>  $formDefinitionIds
     * @return Collection<int, FormField>
     */
    private function sortByFormDefinitions(Collection $fields, array $formDefinitionIds): Collection
    {
        return $fields
            ->sortBy([
                fn (FormField $a, FormField $b): int => array_search($a->form_definition_id, $formDefinitionIds, true) <=> array_search($b->form_definition_id, $formDefinitionIds, true),
                fn (FormField $a, FormField $b): int => $a->sort_order <=> $b->sort_order,
            ])
            ->values();
    }

    /**
     * Empty inputs remove the value. Numbers are stored as numbers, so exports and sorting work.
     */
    private function normalize(FormField $field, mixed $value): mixed
    {
        if ($value === '' || $value === [] || $value === null) {
            return null;
        }

        if ($field->type === FieldType::NUMBER && is_numeric($value)) {
            return $value + 0;
        }

        if ($field->type === FieldType::CHECKBOX && is_array($value)) {
            return array_values($value);
        }

        return $value;
    }

    /**
     * Resending an unchanged value must keep its snapshot, even when the form sends "10.0" for 10 or checked
     * boxes in another order.
     */
    private function isSameValue(FormField $field, mixed $storedValue, mixed $value): bool
    {
        if ($field->type === FieldType::NUMBER && is_numeric($storedValue) && is_numeric($value)) {
            return (float) $storedValue === (float) $value;
        }

        if (is_array($storedValue) && is_array($value)) {
            sort($storedValue);
            sort($value);
        }

        return $storedValue === $value;
    }
}
