<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FieldType;
use App\Models\FormField;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointField;
use App\ValueObjects\MapPointCategoryTree;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Stores the values of category fields on points. The fields of a point come from its category, or from the
 * nearest parent category with fields. A value is active while its field is one of these fields. Otherwise
 * it is a former value: it is kept, but neither shown publicly nor editable. Changing the category back makes
 * it active again.
 */
class MapPointFieldService
{
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
     * and belongs to the point's category is touched. Values of other fields, including former values, are
     * kept. An empty value removes the stored value. A changed value gets a fresh snapshot of the field,
     * because it was entered against the field as it is now.
     *
     * @param  array<string, mixed>  $valuesByFieldUuid
     * @param  Collection<int, FormField>|null  $fields  The fields of the point's category. Pass them when storing many points.
     */
    public function syncValues(MapPoint $mapPoint, array $valuesByFieldUuid, ?Collection $fields = null): void
    {
        $fields ??= $this->fieldsOfCategory($mapPoint->category_id);
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
     * The stored values of the point's current fields in field order. Needs the relation fields.formField.
     *
     * @return Collection<int, MapPointField>
     */
    public function activeFields(MapPoint $mapPoint, MapPointCategoryTree $tree): Collection
    {
        $formDefinitionId = $this->fieldsFormDefinitionId($mapPoint, $tree);

        return $mapPoint->fields
            ->filter(fn (MapPointField $field): bool => $formDefinitionId !== null && $field->formField?->form_definition_id === $formDefinitionId)
            ->sortBy(fn (MapPointField $field): int => $field->formField->sort_order ?? 0)
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

    private function fieldsFormDefinitionId(MapPoint $mapPoint, MapPointCategoryTree $tree): ?int
    {
        return $mapPoint->category_id === null ? null : $tree->fieldsFormDefinitionId($mapPoint->category_id);
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
