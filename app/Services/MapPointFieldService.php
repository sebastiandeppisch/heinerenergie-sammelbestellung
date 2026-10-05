<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FieldType;
use App\Models\FormField;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointField;
use App\Rules\MapPointImageItem;
use App\Rules\MaxImagePixels;
use App\ValueObjects\MapPointCategoryTree;
use App\ValueObjects\StoredImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Throwable;

/**
 * Stores the values of category fields on points. The fields of a point come from its category, or from the
 * nearest parent category with fields. A value is active while its field is one of these fields. Otherwise
 * it is a former value: it is kept, but neither shown publicly nor editable. Changing the category back makes
 * it active again.
 */
class MapPointFieldService
{
    public function __construct(private readonly ImageStorage $imageStorage) {}

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
     * Fields whose values cannot be put into a cell, such as images, are left out.
     *
     * @return Collection<int, FormField>
     */
    public function fieldsUsableInGroup(Group $group): Collection
    {
        return FormField::query()
            ->whereHas('formDefinition.mapPointCategory', fn (Builder $query) => $query->usableInGroup($group))
            ->with(['options', 'formDefinition.mapPointCategory'])
            ->get()
            ->filter(fn (FormField $field): bool => $field->type->supportsSpreadsheet())
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
        $newImages = [];

        try {
            DB::transaction(function () use ($mapPoint, $valuesByFieldUuid, $fields, $storedFields, &$newImages): void {
                foreach ($fields as $field) {
                    if (! array_key_exists($field->uuid, $valuesByFieldUuid)) {
                        continue;
                    }

                    $storedField = $storedFields->get($field->id);
                    $value = $field->type === FieldType::IMAGE
                        ? $this->storeImages($mapPoint, $valuesByFieldUuid[$field->uuid], $newImages)
                        : $this->normalize($field, $valuesByFieldUuid[$field->uuid]);

                    if ($storedField !== null && $this->isSameValue($field, $storedField->value, $value)) {
                        continue;
                    }

                    $storedField?->delete();

                    if ($value !== null) {
                        $field->createMapPointField($mapPoint, $value);
                    }

                    if ($field->type === FieldType::IMAGE && $storedField !== null) {
                        $removedImages = array_values(array_diff((array) $storedField->value, (array) $value));
                        DB::afterCommit(fn () => $this->imageStorage->delete($removedImages));
                    }
                }
            });
        } catch (Throwable $e) {
            $this->imageStorage->delete($newImages);

            throw $e;
        }

        $mapPoint->unsetRelation('fields');
    }

    /**
     * The entries keep a stored image by its file name, upload a new one or copy an existing one. Their order is
     * the order of the images.
     *
     * @param  array<int, string>  $newImages  collects the stored files, to remove them when saving fails
     * @return array<int, string>|null
     */
    private function storeImages(MapPoint $mapPoint, mixed $entries, array &$newImages): ?array
    {
        $paths = [];

        foreach ((array) $entries as $entry) {
            $path = match (true) {
                $entry instanceof UploadedFile => $this->imageStorage->store($entry, $mapPoint->imageDirectory()),
                $entry instanceof StoredImage => $this->imageStorage->copy($entry->path, $mapPoint->imageDirectory()),
                default => null,
            };

            if ($path !== null) {
                $newImages[] = $path;
            }

            $paths[] = $path ?? $mapPoint->imageDirectory().'/'.$entry;
        }

        return $paths === [] ? null : $paths;
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
     * @param  MapPoint|null  $mapPoint  The point being edited. Its images may be kept by their file names.
     * @return array<string, array<int, mixed>>
     */
    public function validationRules(Collection $fields, string $prefix, ?MapPoint $mapPoint = null): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $options = $field->options->pluck('value')->all();

            $fieldRules = match ($field->type) {
                FieldType::SELECT => ['nullable', 'string', Rule::in($options)],
                FieldType::CHECKBOX => ['nullable', 'array'],
                FieldType::IMAGE => ['nullable', 'array', 'max:'.$field->max_images],
                default => array_values(array_diff($field->getValidationRules()[$field->uuid] ?? [], ['required'])),
            };

            if (! in_array('nullable', $fieldRules, true)) {
                array_unshift($fieldRules, 'nullable');
            }

            $rules[$prefix.'.'.$field->uuid] = $fieldRules;

            if ($field->type === FieldType::CHECKBOX) {
                $rules[$prefix.'.'.$field->uuid.'.*'] = ['string', Rule::in($options)];
            }

            if ($field->type === FieldType::IMAGE) {
                $storedNames = $this->storedImageNames($mapPoint, $field);
                $rules[$prefix.'.'.$field->uuid.'.*'] = Rule::forEach(fn (mixed $entry): array => $entry instanceof UploadedFile
                    ? [File::image()->types(['jpg', 'jpeg', 'png'])->max(10 * 1024), new MaxImagePixels]
                    : ['distinct', new MapPointImageItem($storedNames)]);
            }
        }

        return $rules;
    }

    /**
     * @return array<int, string>
     */
    private function storedImageNames(?MapPoint $mapPoint, FormField $field): array
    {
        $paths = $mapPoint?->fields()->where('form_field_id', $field->id)->first()?->value;

        return array_map(basename(...), (array) $paths);
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
     * boxes in another order. The order of images matters.
     */
    private function isSameValue(FormField $field, mixed $storedValue, mixed $value): bool
    {
        if ($field->type === FieldType::NUMBER && is_numeric($storedValue) && is_numeric($value)) {
            return (float) $storedValue === (float) $value;
        }

        if (is_array($storedValue) && is_array($value) && $field->type !== FieldType::IMAGE) {
            sort($storedValue);
            sort($value);
        }

        return $storedValue === $value;
    }
}
