<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Throwable;
use App\Data\FormDefinitionData;
use App\Enums\FieldType;
use App\Http\Requests\StoreFormSubmissionRequest;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Services\CurrentGroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;

class FormSubmitController extends Controller
{
    public function show(FormDefinition $formDefinition)
    {
        app(CurrentGroupService::class)->setGroup($formDefinition->group);

        return Inertia::render('Forms/Show', [
            'formDefinition' => FormDefinitionData::fromModel($formDefinition),
        ]);
    }

    public function submit(StoreFormSubmissionRequest $request, FormDefinition $formDefinition)
    {
        app(CurrentGroupService::class)->setGroup($formDefinition->group);

        $storedImagePaths = [];

        try {
            DB::transaction(function () use ($formDefinition, $request, &$storedImagePaths): void {
                $submission = $formDefinition->createSubmission();
                foreach ($formDefinition->fields as $field) {
                    $field->createSubmissionField($submission, $this->getValueFromField($field, $request, $submission, $storedImagePaths));
                }
                $submission->handleCreators();
            });
        } catch (Throwable $e) {
            Storage::disk('public')->delete($storedImagePaths);
            throw $e;
        }

        return Inertia::render('Forms/Submitted', [
            'formDefinition' => FormDefinitionData::fromModel($formDefinition),
        ]);
    }

    private function getValueFromField(FormField $field, Request $request, FormSubmission $submission, array &$storedImagePaths)
    {
        if ($field->type === FieldType::IMAGE) {
            return $this->storeImages($field, $request, $submission, $storedImagePaths);
        }

        return match ($field->type) {
            FieldType::TEXT => $request->string($field->uuid),
            FieldType::NUMBER => $request->integer($field->uuid),
            default => $request->input($field->uuid),
        };
    }

    /**
     * @param string[] $storedImagePaths
     * @return string[]
     */
    private function storeImages(FormField $field, Request $request, FormSubmission $submission, array &$storedImagePaths): array
    {
        $paths = [];
        $files = $request->file($field->uuid) ?? [];

        foreach ($files as $file) {
            $filename = Str::uuid().'.jpg';
            $directory = 'form-images/'.$submission->uuid;
            $path = $directory.'/'.$filename;

            $image = Image::decode($file);
            $image->scaleDown(width: 1920, height: 1920);
            $encoded = $image->encode(new JpegEncoder(quality: 80));

            Storage::disk('public')->put($path, $encoded);
            $storedImagePaths[] = $path;
            $paths[] = $path;
        }

        return $paths;
    }
}
