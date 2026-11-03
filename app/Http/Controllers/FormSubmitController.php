<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\FormDefinitionData;
use App\Enums\FormType;
use App\Http\Requests\StoreFormSubmissionRequest;
use App\Models\FormDefinition;
use App\Services\CurrentGroupService;
use App\Services\FormEmbedAccessService;
use App\Services\FormSubmissionConfirmationService;
use App\Services\FormSubmissionRecorder;
use App\Services\FormTargetService;
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
        private readonly FormTargetService $targets,
        private readonly FormSubmissionRecorder $recorder,
    ) {}

    public function show(FormDefinition $formDefinition, Request $request): Response
    {
        abort_if($formDefinition->type === FormType::MapPointFields || $formDefinition->isMapPointFollowUp(), 404);

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
        abort_if($formDefinition->type === FormType::MapPointFields || $formDefinition->isMapPointFollowUp(), 404);

        if (! $this->embedAccess->verifyToken($formDefinition, $request->input('_form_token'))) {
            throw new HttpException(422, 'Ungültiges oder abgelaufenes Formular, bitte Seite neu laden.');
        }

        app(CurrentGroupService::class)->setGroup($formDefinition->group);

        $email = $formDefinition->requires_email_confirmation
            ? (string) $request->input($formDefinition->emailField()->uuid ?? '')
            : null;

        if ($email !== null) {
            $this->confirmation->ensureNotRateLimited((string) $request->ip(), $email);
        }

        $storedImagePaths = [];
        $consentChoice = $request->consentChoice();

        try {
            [$submission, $token, $notices] = DB::transaction(function () use ($formDefinition, $request, $email, $consentChoice, &$storedImagePaths): array {
                $submission = $this->recorder->record($formDefinition, $request, $storedImagePaths);

                if ($email !== null) {
                    $submission->update(['target_payload' => $this->targets->prepareOrNull($submission, $consentChoice)]);

                    return [$submission, $this->confirmation->issueToken($submission), new Collection];
                }

                return [$submission, null, $this->targets->run($submission, null, $consentChoice)];
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
}
