<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\FieldType;
use App\Models\FormDefinition;
use App\Models\MapPoint;
use App\Services\MapPointEditLinkService;
use App\Services\MapPointFollowUpService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Override;

/**
 * A follow-up form submitted with the manage link of a point. Without a valid link there is nothing to validate,
 * the controller answers then.
 */
class StoreMapPointFollowUpRequest extends FormRequest
{
    private ?MapPoint $mapPoint = null;

    private bool $resolved = false;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $mapPoint = $this->mapPoint();
        $followUp = $this->followUp();

        if ($mapPoint === null || $followUp === null) {
            return [];
        }

        $rules = $followUp->getValidationRules();
        $storedNames = app(MapPointFollowUpService::class)->storedImageNames($mapPoint, $followUp);

        // Stored images may come back by their file names, they stay unless they were removed. Kept images count for a
        // required field, so that is checked after the rules.
        foreach ($followUp->fields->where('type', FieldType::IMAGE) as $field) {
            $rules[$field->uuid] = array_values(array_diff((array) ($rules[$field->uuid] ?? []), ['required']));
            $imageRules = $rules[$field->uuid.'.*'] ?? [];
            $rules[$field->uuid.'.*'] = Rule::forEach(fn (mixed $entry): array => $entry instanceof UploadedFile
                ? $imageRules
                : ['string', Rule::in($storedNames[$field->uuid] ?? [])]);
        }

        return [
            ...$rules,
            '_removed_images' => ['nullable', 'array'],
            '_removed_images.*' => ['array'],
            '_removed_images.*.*' => ['string', 'max:255'],
        ];
    }

    /**
     * Kept and new images together must fit the field and the category field they are stored in, and a required
     * field needs at least one.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $mapPoint = $this->mapPoint();
                $followUp = $this->followUp();

                if ($mapPoint === null || $followUp === null) {
                    return;
                }

                $followUps = app(MapPointFollowUpService::class);
                $removed = $this->removedImages();
                $storedNames = $followUps->storedImageNames($mapPoint, $followUp);
                $limits = $followUps->imageLimits($followUp);

                foreach ($followUp->fields->where('type', FieldType::IMAGE) as $field) {
                    $maxImages = $limits[$field->uuid] ?? $field->max_images ?? 1;
                    $kept = count(array_diff($storedNames[$field->uuid] ?? [], $removed[$field->uuid] ?? []));
                    $added = count(array_filter((array) $this->file($field->uuid), fn (mixed $file): bool => $file instanceof UploadedFile));

                    if ($added > 0 && $kept + $added > $maxImages) {
                        $validator->errors()->add($field->uuid, $maxImages === 1 ? 'Hier passt ein Bild, entferne erst das vorhandene.' : "Hier passen höchstens {$maxImages} Bilder, entferne erst ein vorhandenes.");
                    } elseif ($field->required && $kept + $added === 0) {
                        $validator->errors()->add($field->uuid, "{$field->label} braucht ein Bild.");
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function attributes(): array
    {
        return $this->followUp()?->getValidationAttributes() ?? [];
    }

    public function mapPoint(): ?MapPoint
    {
        if (! $this->resolved) {
            $this->mapPoint = app(MapPointEditLinkService::class)->findByToken((string) $this->route('token'));
            $this->resolved = true;
        }

        return $this->mapPoint;
    }

    public function followUp(): ?FormDefinition
    {
        $mapPoint = $this->mapPoint();
        $followUp = $mapPoint === null ? null : app(MapPointFollowUpService::class)->formFor($mapPoint);
        $followUp?->loadMissing('fields.options');

        return $followUp;
    }

    /**
     * The file names of stored images the person removed, keyed by the uuid of the form field.
     *
     * @return array<string, array<int, string>>
     */
    public function removedImages(): array
    {
        $removed = [];

        foreach ((array) $this->input('_removed_images', []) as $fieldUuid => $names) {
            $removed[(string) $fieldUuid] = array_values(array_filter((array) $names, is_string(...)));
        }

        return $removed;
    }
}
