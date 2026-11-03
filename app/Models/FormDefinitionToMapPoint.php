<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\ConsentChoiceData;
use App\Data\MapPointTargetPayload;
use App\Enums\FieldType;
use App\Enums\MapPointTargetMode;
use App\Models\Traits\HasUuid;
use App\Services\ImageStorage;
use App\Services\MapPointConsentService;
use Database\Factories\FormDefinitionToMapPointFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Override;

class FormDefinitionToMapPoint extends Model
{
    /** @use HasFactory<FormDefinitionToMapPointFactory> */
    use HasFactory;

    use HasUuid;

    protected $table = 'form_definition_to_map_points';

    protected $attributes = [
        'mode' => 'create',
    ];

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'mode' => MapPointTargetMode::class,
        ];
    }

    public function isUpdate(): bool
    {
        return $this->mode === MapPointTargetMode::UPDATE;
    }

    /**
     * A form whose update target adds to the points this target creates, e.g. photos asked for after the point exists.
     *
     * @return BelongsTo<FormDefinition, $this>
     */
    public function followUpFormDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class, 'follow_up_form_definition_id');
    }

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
     * An email field of the form whose value becomes the contact address of the point, for its manage links and contact requests.
     *
     * @return BelongsTo<FormField, $this>
     */
    public function contactEmailField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'contact_email_field_id');
    }

    /**
     * A text field of the form whose value becomes the location description of the point, e.g. „Im Herrngarten am
     * südlichen Brunnen“.
     *
     * @return BelongsTo<FormField, $this>
     */
    public function locationField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'location_field_id');
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function contactEnabledField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'contact_enabled_field_id');
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
     * A checkbox field of the form whose checked options pick characteristics of the created points.
     *
     * @return BelongsTo<FormField, $this>
     */
    public function characteristicsField(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'characteristics_field_id');
    }

    /**
     * @return HasMany<FormDefinitionToMapPointCharacteristic, $this>
     */
    public function characteristicMappings(): HasMany
    {
        return $this->hasMany(FormDefinitionToMapPointCharacteristic::class);
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
            $this->characteristicMappings()->delete();
            $this->fieldMappings()->delete();

            return parent::delete();
        });
    }

    /**
     * Reads the map point from the submission, including the category and the characteristics its options pick,
     * and the consent of the submitter with the text as they saw it.
     */
    public function prepare(FormSubmission $submission, ConsentChoiceData $consentChoice): MapPointTargetPayload
    {
        $titleField = $this->titleField;
        $descriptionField = $this->descriptionField;
        $coordinateField = $this->coordinateField;

        if ($this->isUpdate() || $titleField === null || $descriptionField === null || $coordinateField === null) {
            throw new LogicException('Only a complete create target prepares a new map point.');
        }

        $categoryId = $this->categoryFor($submission)?->id;
        $characteristicIds = $this->characteristicIdsFor($submission, $categoryId);
        $location = $this->location($submission);
        $consent = app(MapPointConsentService::class)->forTarget($this, $consentChoice, $categoryId, $characteristicIds, $location !== null);

        return new MapPointTargetPayload(
            title: $titleField->getSubmissionField($submission)->value,
            description: $descriptionField->getSubmissionField($submission)->value,
            coordinate: $coordinateField->getSubmissionField($submission)->value,
            category_id: $categoryId,
            group_id: $this->formDefinition->group_id,
            field_values: $this->mappedValues($submission),
            characteristic_ids: $characteristicIds,
            location: $location,
            contact_email: $this->contactEmail($submission),
            contact_enabled: $this->contactEnabled($submission),
            public_consent: $consent->consent,
            public_consent_text: $consent->text,
            public_consent_fields: $consent->fields,
        );
    }

    private function contactEmail(FormSubmission $submission): ?string
    {
        if ($this->contactEmailField === null) {
            return null;
        }

        $email = filter_var($this->contactEmailField->getSubmissionField($submission)->value, FILTER_VALIDATE_EMAIL);

        return $email === false ? null : $email;
    }

    private function location(FormSubmission $submission): ?string
    {
        /** @var mixed $value stored as json, a string for text fields */
        $value = $this->locationField?->submissionFields()->where('form_submission_id', $submission->id)->first()?->value;

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        // The column holds 255 characters. The form builder asks for a field that is not longer, this guards older forms.
        return mb_substr(trim($value), 0, MapPoint::LOCATION_MAX_LENGTH);
    }

    private function contactEnabled(FormSubmission $submission): bool
    {
        if ($this->contactEnabledField === null || $this->contactEnabledField->type !== FieldType::CHECKBOX) {
            return false;
        }

        $value = $this->contactEnabledField->getSubmissionField($submission)->value;

        return is_array($value) ? $value !== [] : filled($value);
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
     * The characteristics picked by the checked options. Options of characteristics the point's category does not
     * offer, e.g. after the characteristic was moved, are ignored.
     *
     * @return array<int, int>
     */
    public function characteristicIdsFor(FormSubmission $submission, ?int $categoryId): array
    {
        if ($this->characteristicsField === null) {
            return [];
        }

        $checkedOptions = (array) $this->characteristicsField->submissionFields()->where('form_submission_id', $submission->id)->first()?->value;
        $tree = MapPointCategory::tree();

        return MapPointCharacteristic::query()
            ->whereIn('id', $this->characteristicMappings()->whereIn('option_value', array_map(strval(...), $checkedOptions))->select('map_point_characteristic_id'))
            ->get()
            ->filter(fn (MapPointCharacteristic $characteristic): bool => $characteristic->isSelectableFor($categoryId, $tree))
            ->modelKeys();
    }

    /**
     * The characteristics the options of the characteristics field can pick for points of the category.
     *
     * @return array<int, int>
     */
    public function offeredCharacteristicIds(?int $categoryId): array
    {
        if ($this->characteristicsField === null) {
            return [];
        }

        $tree = MapPointCategory::tree();

        return MapPointCharacteristic::query()
            ->whereIn('id', $this->characteristicMappings()->select('map_point_characteristic_id'))
            ->get()
            ->filter(fn (MapPointCharacteristic $characteristic): bool => $characteristic->isSelectableFor($categoryId, $tree))
            ->modelKeys();
    }

    /**
     * The submitted values keyed by the uuid of the category field they are stored in.
     *
     * @return array<string, mixed>
     */
    public function mappedValues(FormSubmission $submission): array
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
     * kept as paths, they are copied when the point is created.
     */
    private function convert(FormField $targetField, SubmissionField $submissionField): mixed
    {
        $value = $submissionField->value;

        if ($targetField->type === FieldType::IMAGE) {
            $imageStorage = app(ImageStorage::class);
            $paths = array_filter((array) $value, fn (mixed $path): bool => is_string($path) && $imageStorage->exists($path));

            return array_slice(array_values($paths), 0, $targetField->max_images);
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
