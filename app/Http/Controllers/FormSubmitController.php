<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\FormDefinitionData;
use App\Enums\FieldType;
use App\Http\Requests\StoreFormSubmissionRequest;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Services\CurrentGroupService;
use App\Services\FormEmbedAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class FormSubmitController extends Controller
{
    public function __construct(private readonly FormEmbedAccessService $embedAccess) {}

    public function show(FormDefinition $formDefinition, Request $request): Response
    {
        if (! $this->embedAccess->isEmbedAllowed($formDefinition, $request)) {
            return Inertia::render('Forms/Show', [
                'formDefinition' => null,
                'embedBlocked' => true,
            ]);
        }

        app(CurrentGroupService::class)->setGroup($formDefinition->group);

        return Inertia::render('Forms/Show', [
            'formDefinition' => $this->publicFormData($formDefinition),
            'formToken' => $this->embedAccess->issueToken($formDefinition),
            'embedBlocked' => false,
        ]);
    }

    public function submit(StoreFormSubmissionRequest $request, FormDefinition $formDefinition): Response
    {
        if (! $this->embedAccess->verifyToken($formDefinition, $request->input('_form_token'))) {
            throw new HttpException(422, 'Ungültiges oder abgelaufenes Formular, bitte Seite neu laden.');
        }

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
            'formDefinition' => $this->publicFormData($formDefinition),
        ]);
    }

    /**
     * Form data for the public, anonymous-facing pages. Strips the embed domain
     * whitelist, which is an internal access-control detail and must not be
     * exposed to visitors of the public form.
     */
    private function publicFormData(FormDefinition $formDefinition): FormDefinitionData
    {
        $data = FormDefinitionData::fromModel($formDefinition);
        $data->allowed_embed_domains = null;

        return $data;
    }

    /**
     * @param  string[]  $storedImagePaths
     * @return string|int|string[]|null
     */
    private function getValueFromField(FormField $field, Request $request, FormSubmission $submission, array &$storedImagePaths): string|int|array|null
    {
        if ($field->type === FieldType::IMAGE) {
            return $this->storeImages($field, $request, $submission, $storedImagePaths);
        }

        return match ($field->type) {
            FieldType::TEXT => (string) $request->string($field->uuid),
            FieldType::NUMBER => $request->integer($field->uuid),
            default => $request->input($field->uuid),
        };
    }

    /**
     * @param  string[]  $storedImagePaths
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

            Storage::disk('public')->put($path, $encoded->toStream());
            $storedImagePaths[] = $path;
            $paths[] = $path;
        }

        return $paths;
    }
}
