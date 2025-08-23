<?php

namespace App\Http\Controllers;

use App\Data\FormDefinitionData;
use App\Enums\FieldType;
use App\Http\Requests\StoreFormSubmissionRequest;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Services\CurrentGroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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

        DB::transaction(function () use ($formDefinition, $request) {
            $submission = $formDefinition->createSubmission();
            foreach ($formDefinition->fields as $field) {
                $field->createSubmissionField($submission, $this->getValueFromField($field, $request));
            }
            $submission->handleCreators();
        });

        return Inertia::render('Forms/Submitted', [
            'formDefinition' => FormDefinitionData::fromModel($formDefinition),
        ]);
    }

    private function getValueFromField(FormField $field, Request $request)
    {
        return match ($field->type) {
            FieldType::TEXT => $request->string($field->uuid),
            FieldType::NUMBER => $request->integer($field->uuid),
            default => $request->input($field->uuid),
        };
    }
}
