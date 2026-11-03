<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FieldType;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\FormSubmission;
use Illuminate\Http\Request;

/**
 * Stores the values of a submitted form, for the public forms and the follow-up forms of map points.
 */
class FormSubmissionRecorder
{
    public function __construct(private readonly ImageStorage $imageStorage) {}

    /**
     * Uploaded images are stored on the disk right away. A failing transaction does not remove them, so the caller
     * deletes the collected paths then.
     *
     * @param  array<int, string>  $storedImagePaths
     */
    public function record(FormDefinition $formDefinition, Request $request, array &$storedImagePaths): FormSubmission
    {
        $submission = $formDefinition->createSubmission();

        foreach ($formDefinition->fields as $field) {
            $field->createSubmissionField($submission, $this->valueOf($field, $request, $submission, $storedImagePaths));
        }

        return $submission;
    }

    /**
     * @param  array<int, string>  $storedImagePaths
     * @return string|int|float|array<mixed>|null
     */
    private function valueOf(FormField $field, Request $request, FormSubmission $submission, array &$storedImagePaths): string|int|float|array|null
    {
        return match ($field->type) {
            FieldType::IMAGE => $this->storeImages($field, $request, $submission, $storedImagePaths),
            FieldType::TEXT => (string) $request->string($field->uuid),
            FieldType::NUMBER => $this->toNumberOrNull($request->input($field->uuid)),
            FieldType::ADDRESS => $this->normalizeAddress($request->input($field->uuid)),
            default => $request->input($field->uuid),
        };
    }

    /**
     * Keeps decimals such as 9.9 kWp, and stores an empty field as empty instead of 0.
     */
    private function toNumberOrNull(mixed $value): int|float|null
    {
        return is_numeric($value) ? $value + 0 : null;
    }

    /**
     * An optional address that was left untouched arrives as an array of empty parts.
     * It is stored as null so that consumers get "no address" instead of an empty shell.
     *
     * @return array<string, mixed>|null
     */
    private function normalizeAddress(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $filledParts = array_filter(
            $value,
            fn (mixed $part): bool => $part !== null && $part !== '',
        );

        return $filledParts === [] ? null : $value;
    }

    /**
     * @param  array<int, string>  $storedImagePaths
     * @return array<int, string>
     */
    private function storeImages(FormField $field, Request $request, FormSubmission $submission, array &$storedImagePaths): array
    {
        $paths = [];

        foreach ($request->file($field->uuid) ?? [] as $file) {
            $path = $this->imageStorage->store($file, $submission->imageDirectory());
            $storedImagePaths[] = $path;
            $paths[] = $path;
        }

        return $paths;
    }
}
