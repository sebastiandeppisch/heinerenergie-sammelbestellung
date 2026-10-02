<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\FormDefinitionData;
use App\Enums\FieldType;
use App\Enums\FormType;
use App\Http\Requests\StoreFormSubmissionRequest;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Services\CurrentGroupService;
use App\Services\FormEmbedAccessService;
use App\Services\FormSubmissionConfirmationService;
use App\Services\ImageStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class FormSubmitController extends Controller
{
    public function __construct(
        private readonly FormEmbedAccessService $embedAccess,
        private readonly ImageStorage $imageStorage,
        private readonly FormSubmissionConfirmationService $confirmation,
    ) {}

    public function show(FormDefinition $formDefinition, Request $request): Response
    {
        abort_if($formDefinition->type === FormType::MapPointFields, 404);

        if (! $this->embedAccess->isEmbedAllowed($formDefinition, $request)) {
            return Inertia::render('Forms/Show', [
                'formDefinition' => null,
                'embedBlocked' => true,
            ]);
        }

        app(CurrentGroupService::class)->setGroup($formDefinition->group);

        return Inertia::render('Forms/Show', [
            'formDefinition' => FormDefinitionData::forPublic($formDefinition),
            'formToken' => $this->embedAccess->issueToken($formDefinition),
            'embedBlocked' => false,
        ]);
    }

    public function submit(StoreFormSubmissionRequest $request, FormDefinition $formDefinition): Response
    {
        abort_if($formDefinition->type === FormType::MapPointFields, 404);

        if (! $this->embedAccess->verifyToken($formDefinition, $request->input('_form_token'))) {
            throw new HttpException(422, 'Ungültiges oder abgelaufenes Formular, bitte Seite neu laden.');
        }

        app(CurrentGroupService::class)->setGroup($formDefinition->group);

        $email = $formDefinition->requires_email_confirmation
            ? (string) $request->input($formDefinition->emailField()?->uuid ?? '')
            : null;

        if ($email !== null) {
            $this->confirmation->ensureNotRateLimited((string) $request->ip(), $email);
        }

        $storedImagePaths = [];

        try {
            [$submission, $token, $notices] = DB::transaction(function () use ($formDefinition, $request, $email, &$storedImagePaths): array {
                $submission = $formDefinition->createSubmission();
                foreach ($formDefinition->fields as $field) {
                    $field->createSubmissionField($submission, $this->getValueFromField($field, $request, $submission, $storedImagePaths));
                }

                if ($email !== null) {
                    return [$submission, $this->confirmation->issueToken($submission), new Collection];
                }

                return [$submission, null, $submission->handleCreators()];
            });
        } catch (Throwable $e) {
            $this->imageStorage->delete($storedImagePaths);
            throw $e;
        }

        if ($email !== null && $token !== null) {
            $this->confirmation->sendMail($submission, $email, $token);

            return Inertia::render('Forms/ConfirmationPending', [
                'formDefinition' => FormDefinitionData::forPublic($formDefinition),
            ]);
        }

        return Inertia::render('Forms/Submitted', [
            'formDefinition' => FormDefinitionData::forPublic($formDefinition),
            'notices' => $notices,
        ]);
    }

    /**
     * @param  string[]  $storedImagePaths
     * @return string|int|string[]|null
     */
    private function getValueFromField(FormField $field, Request $request, FormSubmission $submission, array &$storedImagePaths): string|int|float|array|null
    {
        if ($field->type === FieldType::IMAGE) {
            return $this->storeImages($field, $request, $submission, $storedImagePaths);
        }

        return match ($field->type) {
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
     * @param  string[]  $storedImagePaths
     * @return string[]
     */
    private function storeImages(FormField $field, Request $request, FormSubmission $submission, array &$storedImagePaths): array
    {
        $paths = [];
        $files = $request->file($field->uuid) ?? [];

        foreach ($files as $file) {
            $path = $this->imageStorage->store($file, 'form-images/'.$submission->uuid);
            $storedImagePaths[] = $path;
            $paths[] = $path;
        }

        return $paths;
    }
}
