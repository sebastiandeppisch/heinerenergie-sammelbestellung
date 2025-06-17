<?php

namespace App\Http\Controllers;

use App\Data\FormDefinitionData;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\IndexFormSubmission;

class FormSubmissionController extends Controller
{
    public function index(IndexFormSubmission $request){
        return Inertia::render('FormSubmissions/Index', [
            'formDefinitions' => FormDefinition::all()->map(fn(FormDefinition $formDefinition) => FormDefinitionData::fromModel($formDefinition)),
            'selectedFormDefinitions' => $request->selectedFormDefinitions(),
            'sortOrder' => $request->sorting(),
            'groupByForm' => $request->groupByForm(), 
            'dateTo' =>  $request->dateTo(),
            'dateFrom' => $request->dateFrom(),
        ]);
    }
}
