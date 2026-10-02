<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FieldType;
use App\Events\MapPointCreatedByFormSubmission;
use App\Models\Traits\HasUuid;
use App\Services\ImageStorage;
use App\Services\MapPointFieldService;
use App\ValueObjects\StoredImage;
use Database\Factories\FormDefinitionToMapPointFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Override;

class FormDefinitionToMapPoint extends Model
{
    /** @use HasFactory<FormDefinitionToMapPointFactory> */
    use HasFactory;

    use HasUuid;

    protected $table = 'form_definition_to_map_points';

    /**
     * @return BelongsTo<FormDefinition, $this>
     */
    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function titleField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'title_field_id');
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function descriptionField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'description_field_id');
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function coordinateField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'coordinate_field_id');
    }

    /**
     * The category of the created points, unless an option of the sub category field picks a sub category.
     *
     * @return BelongsTo<MapPointCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MapPointCategory::class, 'map_point_category_id');
    }

    /**
     * A select or radio field of the form whose options pick the sub category.
     *
     * @return BelongsTo<FormField, $this>
     */
    public function subcategoryField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'subcategory_field_id');
    }

    /**
     * @return HasMany<FormDefinitionToMapPointSubcategory, $this>
     */
    public function subcategories(): HasMany
    {
        return $this->hasMany(FormDefinitionToMapPointSubcategory::class);
    }

    /**
     * @return HasMany<FormDefinitionToMapPointField, $this>
     */
    public function fieldMappings(): HasMany
    {
        return $this->hasMany(FormDefinitionToMapPointField::class);
    }

    /**
     * The foreign keys do not cascade, so the rows belonging to this mapping are deleted here.
     */
    #[Override]
    public function delete(): ?bool
    {
        return DB::transaction(function (): ?bool {
            $this->subcategories()->delete();
            $this->fieldMappings()->delete();

            return parent::delete();
        });
    }

    public function createMapPoint(FormSubmission $submission): MapPoint
    {
        $mapPoint = DB::transaction(function () use ($submission) {
            $titleField = $this->titleField->getSubmissionField($submission);
            $descriptionField = $this->descriptionField->getSubmissionField($submission);
            $coordinateField = $this->coordinateField->getSubmissionField($submission);

            // Extract lat/lng from coordinate field value
            $coordinateValue = $coordinateField->value;

            $mapPoint = MapPoint::create([
                'group_id' => $this->formDefinition->group_id,
                'title' => $titleField->value,
                'description' => $descriptionField->value,
                'coordinate' => $coordinateValue,
                'published' => false, // unpublished initially
                'category_id' => $this->categoryFor($submission)?->id,
            ]);

            $fieldService = app(MapPointFieldService::class);
            $fields = $fieldService->fieldsOfCategory($mapPoint->category_id);
            // A value that does not fit its field is left out, the point is created anyway.
            $fieldService->syncValues($mapPoint, $fieldService->validValues($fields, $this->mappedValues($submission)));

            // Associate with the form submission as pointable
            // @phpstan-ignore argument.type (Pointable<FormSubmission> satisfies Pointable<Model> at runtime; PHPStan invariance limitation)
            $mapPoint->pointable()->associate($submission);
            $mapPoint->save();

            event(new MapPointCreatedByFormSubmission($mapPoint, $submission));

            return $mapPoint->fresh();
        });

        return $mapPoint;
    }

    /**
     * The sub category picked by the submitted option, if it is still below the category. Otherwise the category.
     */
    private function categoryFor(FormSubmission $submission): ?MapPointCategory
    {
        if ($this->category === null) {
            return null;
        }

        $selectedOption = $this->subcategoryField?->submissionFields()->where('form_submission_id', $submission->id)->first()?->value;
        $subcategory = $this->subcategories->firstWhere('option_value', $selectedOption)?->category;

        if ($subcategory !== null && in_array($subcategory->id, MapPointCategory::tree()->descendantIds($this->category->id), true)) {
            return $subcategory;
        }

        return $this->category;
    }

    /**
     * The submitted values keyed by the uuid of the category field they are stored in.
     *
     * @return array<string, mixed>
     */
    private function mappedValues(FormSubmission $submission): array
    {
        $values = [];
        $submissionFields = $submission->submissionFields()->with('options')->get()->keyBy('form_field_id');

        foreach ($this->fieldMappings()->with('targetField.options')->get() as $fieldMapping) {
            $submissionField = $submissionFields->get($fieldMapping->source_field_id);

            if ($submissionField !== null) {
                $values[$fieldMapping->targetField->uuid] = $this->convert($fieldMapping->targetField, $submissionField);
            }
        }

        return $values;
    }

    /**
     * Options of the form and of the category are matched by their label, because their values differ. Images are
     * copied, because the submission and the point are deleted independently.
     */
    private function convert(FormField $targetField, SubmissionField $submissionField): mixed
    {
        $value = $submissionField->value;

        if ($targetField->type === FieldType::IMAGE) {
            $imageStorage = app(ImageStorage::class);
            $paths = array_filter((array) $value, fn (mixed $path): bool => is_string($path) && $imageStorage->exists($path));

            return array_map(fn (string $path): StoredImage => new StoredImage($path), array_slice(array_values($paths), 0, $targetField->max_images));
        }

        if (! $targetField->type->supportsOptions()) {
            return is_array($value) ? null : $value;
        }

        $sourceLabels = $submissionField->options->pluck('label', 'value');
        $targetValuesByLabel = $targetField->options->mapWithKeys(fn (FormFieldOption $option): array => [Str::lower(trim($option->label)) => $option->value]);
        $toTargetValue = fn (mixed $sourceValue): ?string => $targetValuesByLabel[Str::lower(trim((string) ($sourceLabels[$sourceValue] ?? $sourceValue)))] ?? null;

        if ($targetField->type === FieldType::CHECKBOX) {
            return array_values(array_filter(array_map($toTargetValue, (array) $value)));
        }

        return is_array($value) ? null : $toTargetValue($value);
    }
}
