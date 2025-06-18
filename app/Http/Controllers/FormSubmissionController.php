<?php

namespace App\Http\Controllers;

use App\Data\FormDefinitionData;
use App\Data\FormSubmissionData;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\IndexFormSubmission;
use App\Data\PaginationData;
use Illuminate\Support\Collection;

class FormSubmissionController extends Controller
{
    public function index(IndexFormSubmission $request){
        $formsubmissions = FormSubmission::query()
            ->with(['submissionFields']);

        if($request->groupByForm()){
            $formsubmissions = $formsubmissions->orderBy('form_definition_id');
        }
        $formsubmissions = $formsubmissions->orderBy('submitted_at', $request->sorting())
            ->where(function ($query) use ($request) {
                if ($request->dateFrom()) {
                    $query->where('submitted_at', '>=', $request->dateFrom());
                }
                if ($request->dateTo()) {
                    $query->where('submitted_at', '<=', $request->dateTo());
                }
            })
            ->when($request->selectedFormDefinitions(), function ($query) use ($request) {
                $query->whereIn('form_definition_id', $request->selectedFormDefinitions());
            })->paginate(10);

        return Inertia::render('FormSubmissions/Index', [
            'formDefinitions' => FormDefinition::all()->map(fn(FormDefinition $formDefinition) => FormDefinitionData::fromModel($formDefinition)),
            'selectedFormDefinitions' => $request->selectedFormDefinitions(),
            'sortOrder' => $request->sorting(),
            'groupByForm' => $request->groupByForm(),
            'dateTo' =>  $request->dateTo(),
            'dateFrom' => $request->dateFrom(),
            'formSubmissions' => Inertia::deepMerge( $this->addPagedIndex($formsubmissions->items(), $formsubmissions->currentPage())),
            'pagination' => PaginationData::fromPagination($formsubmissions),
        ]);
    }

    private function addPagedIndex(array $items, int $page): Collection{
        return collect($items)->mapWithKeys(function ($item, $key) use ($page) {
            $index = ($key + ($page - 1) * 10);

            $item = FormSubmissionData::fromModel($item);

            return [$index => $item];
        });
    }

    private function singleSubmission(FormSubmission $formSubmission, Request $request){
        $index = $request->input('index');
        $data = [];
        $data[$index] = FormSubmissionData::fromModel($formSubmission);
        return Inertia::render('FormSubmissions/Index', [
            'formSubmissions' => Inertia::deepMerge($data)
        ]);

    }

    public function markSeen(Request $request, FormSubmission $formSubmission)
    {
        $formSubmission->seen = true;
        $formSubmission->save();

        return $this->singleSubmission($formSubmission, $request)->with('success', 'Der Formulareintrag wurde als gelesen markiert');
    }

    public function markUnseen(Request $request, FormSubmission $formSubmission)
    {
        $formSubmission->seen = false;
        $formSubmission->save();

        return $this->singleSubmission($formSubmission, $request)->with('success', 'Der Formulareintrag wurde als ungelesen markiert');
    }

}
