<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\FormDefinitionData;
use App\Models\FormSubmission;
use App\Services\CurrentGroupService;
use App\Services\FormSubmissionConfirmationService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Opening the link only shows a button, confirming needs a POST. Mail scanners follow links on their own
 * and would otherwise confirm every address.
 */
class FormConfirmationController extends Controller
{
    public function __construct(private readonly FormSubmissionConfirmationService $confirmation) {}

    public function show(string $token): Response
    {
        $submission = $this->confirmation->findByToken($token);

        if ($submission === null || $submission->formDefinition === null) {
            return $this->status('invalid');
        }

        if (! $submission->isAwaitingConfirmation()) {
            return $this->status('already_confirmed', submission: $submission);
        }

        if ($submission->isConfirmationExpired()) {
            return $this->status('expired', $submission);
        }

        return $this->status('pending', submission: $submission, token: $token);
    }

    public function confirm(string $token): Response
    {
        $submission = $this->confirmation->findByToken($token);

        if ($submission === null || $submission->formDefinition === null) {
            return $this->status('invalid');
        }

        if ($submission->isAwaitingConfirmation() && $submission->isConfirmationExpired()) {
            return $this->status('expired', $submission);
        }

        app(CurrentGroupService::class)->setGroup($submission->formDefinition->group);

        $notices = $this->confirmation->confirm($submission);

        if ($notices === null) {
            return $this->status('already_confirmed', submission: $submission);
        }

        return Inertia::render('Forms/Submitted', [
            'formDefinition' => FormDefinitionData::forPublic($submission->formDefinition),
            'notices' => $notices,
        ]);
    }

    private function status(string $status, ?FormSubmission $submission = null, ?string $token = null): Response
    {
        return Inertia::render('Forms/Confirm', [
            'status' => $status,
            'formName' => $submission?->form_name,
            'token' => $token,
        ]);
    }
}
