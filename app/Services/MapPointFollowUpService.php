<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ImageData;
use App\Enums\FieldType;
use App\Models\FormDefinition;
use App\Models\FormDefinitionToMapPoint;
use App\Models\FormDefinitionToMapPointCharacteristic;
use App\Models\FormDefinitionToMapPointField;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormSubmission;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointField;
use App\ValueObjects\StoredImage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/**
 * Follow-up forms let the person behind a point add to it, e.g. photos, more fields or characteristics. They write
 * only fields and characteristics of the point, never title, description, location, category, consent or contact.
 * A follow-up form is reachable only with the manage link of the point.
 */
class MapPointFollowUpService
{
    public function __construct(private readonly MapPointFieldService $fields) {}

    /**
     * The follow-up form of the target the point was created by.
     */
    public function formFor(MapPoint $mapPoint): ?FormDefinition
    {
        $creator = $this->creatorOf($mapPoint);
        $followUp = $creator?->followUpFormDefinition;

        return $creator !== null && $followUp !== null && $this->stillFits($creator, $followUp) ? $followUp : null;
    }

    /**
     * Either form may have changed since they were linked, so the link counts only while it would still be accepted.
     */
    public function stillFits(FormDefinitionToMapPoint $creator, FormDefinition $followUp): bool
    {
        $followUpCreator = $followUp->mapPointCreator;

        return $followUpCreator?->isUpdate() === true
            && $followUp->group_id === $creator->formDefinition?->group_id
            && $this->fits($this->intOrNull($creator->map_point_category_id), $this->intOrNull($followUpCreator->map_point_category_id));
    }

    public function buttonTextFor(MapPoint $mapPoint): ?string
    {
        return $this->formFor($mapPoint) === null ? null : $this->creatorOf($mapPoint)?->follow_up_button_text;
    }

    /**
     * Points of a create target in the category can take the follow-up form of an update target in the other
     * category when both lie in one branch of the tree and share their fields, so its field mappings fit the point.
     */
    public function fits(?int $createCategoryId, ?int $followUpCategoryId): bool
    {
        // Without a category a follow-up form has no fields to write.
        if ($createCategoryId === null || $followUpCategoryId === null) {
            return false;
        }

        $tree = MapPointCategory::tree();
        $sameBranch = in_array($createCategoryId, [$followUpCategoryId, ...$tree->descendantIds($followUpCategoryId)], true)
            || in_array($followUpCategoryId, $tree->descendantIds($createCategoryId), true);

        return $sameBranch && $tree->fieldsCategoryId($createCategoryId) === $tree->fieldsCategoryId($followUpCategoryId);
    }

    /**
     * The current values of the point in the fields of the follow-up form, keyed by the uuid of the form field.
     * Options are matched by their label, images are given by their file names.
     *
     * @return array<string, mixed>
     */
    public function initialData(MapPoint $mapPoint, FormDefinition $followUp): array
    {
        $creator = $this->updateCreatorOf($followUp);
        $data = [];

        foreach ($this->activeMappings($mapPoint, $creator) as [$mapping, $storedField]) {
            $data[$mapping->sourceField->uuid] = $mapping->targetField->type === FieldType::IMAGE
                ? array_map(basename(...), (array) $storedField->value)
                : $this->toSourceValue($mapping->targetField, $mapping->sourceField, $storedField->value);
        }

        if ($creator->characteristicsField !== null) {
            $characteristicIds = $this->characteristicIdsOf($mapPoint);
            $data[$creator->characteristicsField->uuid] = $creator->characteristicMappings
                ->filter(fn (FormDefinitionToMapPointCharacteristic $option): bool => in_array($option->map_point_characteristic_id, $characteristicIds, true))
                ->pluck('option_value')
                ->map(fn (mixed $value): string => (string) $value)
                ->values()
                ->all();
        }

        return $data;
    }

    /**
     * The images the point holds in the image fields of the follow-up form, served under the token of the manage link.
     *
     * @return array<string, array<int, ImageData>>
     */
    public function storedImages(MapPoint $mapPoint, FormDefinition $followUp, string $token): array
    {
        $images = [];

        foreach ($this->activeMappings($mapPoint, $this->updateCreatorOf($followUp)) as [$mapping, $storedField]) {
            if ($mapping->targetField->type === FieldType::IMAGE) {
                $images[$mapping->sourceField->uuid] = array_map(
                    fn (string $path): ImageData => new ImageData(basename($path), route('map-point.manage.image', [$token, basename($path)])),
                    (array) $storedField->value,
                );
            }
        }

        return $images;
    }

    /**
     * The file names of the images the point holds for each image field of the follow-up form.
     *
     * @return array<string, array<int, string>>
     */
    public function storedImageNames(MapPoint $mapPoint, FormDefinition $followUp): array
    {
        $names = [];

        foreach ($this->activeMappings($mapPoint, $this->updateCreatorOf($followUp)) as [$mapping, $storedField]) {
            if ($mapping->targetField->type === FieldType::IMAGE) {
                $names[$mapping->sourceField->uuid] = array_map(basename(...), (array) $storedField->value);
            }
        }

        return $names;
    }

    /**
     * How many images each image field of the follow-up form takes: no more than the category field it fills holds.
     *
     * @return array<string, int>
     */
    public function imageLimits(FormDefinition $followUp): array
    {
        $limits = [];

        foreach ($this->updateCreatorOf($followUp)->fieldMappings()->with(['targetField', 'sourceField'])->get() as $mapping) {
            if ($mapping->sourceField->type === FieldType::IMAGE && $mapping->targetField->type === FieldType::IMAGE) {
                $limits[$mapping->sourceField->uuid] = min($mapping->sourceField->max_images ?? 1, $mapping->targetField->max_images ?? 1);
            }
        }

        return $limits;
    }

    /**
     * Writes the submission into the point. A field left empty keeps its value, only images are removed when the
     * person removed them. Checked characteristics are added, unchecked ones the form offers are removed, their values
     * become former values. Values that do not fit their field, or fields the point does not have, are skipped.
     *
     * @param  array<string, array<int, string>>  $removedImages  file names keyed by the uuid of the form field
     */
    public function apply(MapPoint $mapPoint, FormSubmission $submission, array $removedImages): void
    {
        $creator = $this->updateCreatorOf($submission->formDefinition);

        DB::transaction(function () use ($mapPoint, $submission, $removedImages, $creator): void {
            $characteristicIds = $this->characteristicIdsAfter($mapPoint, $creator, $submission);
            $mapPoint->characteristics()->sync($characteristicIds);
            $mapPoint->unsetRelation('characteristics');

            $fields = $this->fields->effectiveFields($mapPoint->category_id, $characteristicIds);
            $values = $this->valuesToWrite($mapPoint, $creator, $creator->mappedValues($submission), $fields, $removedImages);

            $this->fields->syncValues($mapPoint, $this->fields->validValues($fields, $values, $mapPoint), $fields);
            $mapPoint->touch();
        });
    }

    /**
     * @return array<int, int>
     */
    private function characteristicIdsAfter(MapPoint $mapPoint, FormDefinitionToMapPoint $creator, FormSubmission $submission): array
    {
        $current = $this->characteristicIdsOf($mapPoint);

        if ($creator->characteristicsField === null) {
            return $current;
        }

        $offered = $creator->offeredCharacteristicIds($mapPoint->category_id);
        $checked = $creator->characteristicIdsFor($submission, $mapPoint->category_id);

        return array_values(array_unique([...array_diff($current, $offered), ...$checked]));
    }

    /**
     * @param  array<string, mixed>  $mappedValues  keyed by the uuid of the target field
     * @param  Collection<int, FormField>  $fields
     * @param  array<string, array<int, string>>  $removedImages
     * @return array<string, mixed>
     */
    private function valuesToWrite(MapPoint $mapPoint, FormDefinitionToMapPoint $creator, array $mappedValues, Collection $fields, array $removedImages): array
    {
        $values = [];
        $sourceUuids = $creator->fieldMappings()->with(['targetField', 'sourceField'])->get()
            ->mapWithKeys(fn (FormDefinitionToMapPointField $mapping): array => [$mapping->targetField->uuid => $mapping->sourceField->uuid]);
        $storedFields = $mapPoint->fields()->get()->keyBy('form_field_id');

        foreach ($fields as $field) {
            if (! array_key_exists($field->uuid, $mappedValues)) {
                continue;
            }

            if ($field->type === FieldType::IMAGE) {
                $removed = $removedImages[$sourceUuids[$field->uuid] ?? ''] ?? [];
                $kept = array_values(array_diff(array_map(basename(...), (array) $storedFields->get($field->id)?->value), $removed));
                $added = array_map(fn (string $path): StoredImage => new StoredImage($path), (array) $mappedValues[$field->uuid]);

                if ($added !== [] || $removed !== []) {
                    $values[$field->uuid] = array_slice([...$kept, ...$added], 0, $field->max_images ?? 1);
                }

                continue;
            }

            if (! $this->isEmpty($mappedValues[$field->uuid])) {
                $values[$field->uuid] = $mappedValues[$field->uuid];
            }
        }

        return $values;
    }

    /**
     * @return array<int, int>
     */
    private function characteristicIdsOf(MapPoint $mapPoint): array
    {
        return $mapPoint->characteristics()->pluck('map_point_characteristics.id')->map(fn (mixed $id): int => (int) $id)->all();
    }

    private function intOrNull(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * The mappings of the follow-up form whose category field holds a value of the point that is active now.
     *
     * @return array<int, array{0: FormDefinitionToMapPointField, 1: MapPointField}>
     */
    private function activeMappings(MapPoint $mapPoint, FormDefinitionToMapPoint $creator): array
    {
        $mapPoint->load('fields.formField');
        $activeFields = $this->fields->activeFields($mapPoint, MapPointCategory::tree())->keyBy('form_field_id');
        $result = [];

        foreach ($creator->fieldMappings()->with(['targetField.options', 'sourceField.options'])->get() as $mapping) {
            $storedField = $activeFields->get($mapping->target_field_id);

            if ($storedField !== null) {
                $result[] = [$mapping, $storedField];
            }
        }

        return $result;
    }

    /**
     * The reverse of the conversion when a point is created: options of the category field are matched to the
     * options of the form field by their label.
     */
    private function toSourceValue(FormField $targetField, FormField $sourceField, mixed $value): mixed
    {
        if (! $targetField->type->supportsOptions()) {
            return $value;
        }

        $targetLabels = $targetField->options->pluck('label', 'value');
        $sourceValuesByLabel = $sourceField->options->mapWithKeys(fn (FormFieldOption $option): array => [Str::lower(trim($option->label)) => $option->value]);
        $toSourceValue = fn (mixed $targetValue): ?string => $sourceValuesByLabel[Str::lower(trim((string) ($targetLabels[$targetValue] ?? $targetValue)))] ?? null;

        if (is_array($value)) {
            return array_values(array_filter(array_map($toSourceValue, $value)));
        }

        return $sourceField->type === FieldType::CHECKBOX ? array_values(array_filter([$toSourceValue($value)])) : $toSourceValue($value);
    }

    private function creatorOf(MapPoint $mapPoint): ?FormDefinitionToMapPoint
    {
        $submission = $mapPoint->loadMissing('pointable')->pointable;
        $creator = $submission instanceof FormSubmission ? $submission->formDefinition?->mapPointCreator : null;

        return $creator?->isUpdate() === false ? $creator : null;
    }

    private function updateCreatorOf(?FormDefinition $followUp): FormDefinitionToMapPoint
    {
        $creator = $followUp?->mapPointCreator;

        throw_unless($creator?->isUpdate() === true, LogicException::class, 'The form does not add to existing map points.');

        return $creator;
    }
}
