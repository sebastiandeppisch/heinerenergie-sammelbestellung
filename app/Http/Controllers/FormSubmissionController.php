<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Context\GroupContextContract;
use App\Data\FormDefinitionData;
use App\Data\FormSubmissionData;
use App\Data\PaginationData;
use App\Enums\FormType;
use App\Http\Requests\IndexFormSubmissionRequest;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class FormSubmissionController extends Controller
{
    public function index(IndexFormSubmissionRequest $request, GroupContextContract $groupContext): Response
    {
        $formsubmissions = FormSubmission::query()
            ->with(['submissionFields', 'submissionFields.formField', 'submissionFields.formField.options', 'submissionFields.options']);

        if ($request->groupByForm()) {
            $formsubmissions = $formsubmissions->orderBy('form_definition_id');
        }

        if ($groupContext->getCurrentGroup() !== null) {
            $formsubmissions = $formsubmissions->where('group_id', $groupContext->getCurrentGroup()->id);
        }

        $formsubmissions = $formsubmissions->orderBy('submitted_at', $request->sorting())
            ->whereHas('formDefinition', function ($query): void {
                $query->where('type', FormType::Form);
            })
            ->where(function ($query) use ($request): void {
                if ($request->dateFrom()) {
                    $query->where('submitted_at', '>=', $request->dateFrom());
                }
                if ($request->dateTo()) {
                    $query->where('submitted_at', '<=', $request->dateTo());
                }
            })
            ->when($request->selectedFormDefinitions(), function ($query) use ($request): void {
                $query->whereIn('form_definition_id', $request->selectedFormDefinitions());
            })->paginate(10);

        $formDefinitions = FormDefinition::with(['fields', 'adviceCreator', 'mapPointCreator'])
            ->where('type', FormType::Form);

        if ($groupContext->getCurrentGroup() !== null) {
            $formDefinitions = $formDefinitions->where('group_id', $groupContext->getCurrentGroup()->id);
        }

        $formDefinitions = $formDefinitions->get()->map(fn (FormDefinition $formDefinition): FormDefinitionData => FormDefinitionData::fromModel($formDefinition));

        $selectedFormDefinitions = FormDefinition::whereIn('id', $request->selectedFormDefinitions())->pluck('uuid')->toArray();

        return Inertia::render('FormSubmissions/Index', [
            'formDefinitions' => $formDefinitions,
            'selectedFormDefinitions' => $selectedFormDefinitions,
            'sortOrder' => $request->sorting(),
            'groupByForm' => $request->groupByForm(),
            'dateTo' => $request->dateTo(),
            'dateFrom' => $request->dateFrom(),
            'view' => $request->view(),
            'formSubmissions' => $this->addPagedIndex($formsubmissions->items(), $formsubmissions->currentPage()),
            'pagination' => PaginationData::fromPagination($formsubmissions),
        ]);
    }

    /**
     * @param  array<int, FormSubmission>  $items
     * @return Collection<int, FormSubmissionData>
     */
    private function addPagedIndex(array $items, int $page): Collection
    {
        return collect($items)->mapWithKeys(function ($item, $key) use ($page): array {
            $index = ($key + ($page - 1) * 10);

            $item = FormSubmissionData::fromModel($item);

            return [$index => $item];
        });
    }

    public function markSeen(Request $request, FormSubmission $formSubmission): Response
    {
        $formSubmission->seen = true;
        $formSubmission->save();

        return back()->with('success', 'Der Formulareintrag wurde als gelesen markiert');
    }

    public function markUnseen(Request $request, FormSubmission $formSubmission): Response
    {
        $formSubmission->seen = false;
        $formSubmission->save();

        return back()->with('success', 'Der Formulareintrag wurde als ungelesen markiert');
    }
}
