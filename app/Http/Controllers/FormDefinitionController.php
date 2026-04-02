<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Context\GroupContextContract;
use App\Data\FormDefinitionData;
use App\Enums\FieldType;
use App\Http\Requests\StoreFormDefinitionFromTemplateRequest;
use App\Http\Requests\UpsertFormDefinitionRequest;
use App\Models\FormDefinition;
use App\Models\Group;
use App\Services\FormDefinitionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FormDefinitionController extends Controller
{
    /**
     * Display a listing of the form definitions.
     */
    public function index(GroupContextContract $groupContext): Response
    {
        $formDefinitions = FormDefinition::with(['fields', 'fields.options', 'group', 'adviceCreator', 'mapPointCreator']);

        if ($groupContext->getCurrentGroup() !== null) {
            $formDefinitions = $formDefinitions->where('group_id', $groupContext->getCurrentGroup()->id);
        }

        $formDefinitions = $formDefinitions->get()->map(fn (FormDefinition $formDefinition): FormDefinitionData => FormDefinitionData::fromModel($formDefinition));

        $groups = Group::all()->map(fn (Group $group): array => [
            'id' => $group->uuid,
            'name' => $group->name,
        ]);

        return Inertia::render('FormBuilder/Index', [
            'formDefinitions' => $formDefinitions,
            'groups' => $groups,
        ]);
    }

    /**
     * Show the form for creating a new form definition.
     */
    public function create(): Response
    {
        $groups = Group::all()->map(fn (Group $group): array => [
            'id' => $group->uuid,
            'name' => $group->name,
        ]);

        return Inertia::render('FormBuilder/Edit', [
            'formDefinition' => null,
            'fieldTypes' => $this->activeFieldTypes(),
            'isEdit' => false,
            'groups' => $groups,
        ]);
    }

    /**
     * @return array<int, FieldType>
     */
    private function activeFieldTypes(): array
    {
        $inactive = collect([
            FieldType::FILE,
        ]);

        return collect(FieldType::cases())->filter(fn ($case): bool => ! $inactive->contains($case))->values()->toArray();
    }

    /**
     * Show the form for editing the specified form definition.
     */
    public function edit(FormDefinition $formDefinition): Response
    {
        $formDefinition->load('fields.options', 'adviceCreator.firstNameField', 'adviceCreator.lastNameField', 'adviceCreator.addressField', 'adviceCreator.emailField', 'adviceCreator.phoneField', 'adviceCreator.adviceTypeField', 'mapPointCreator.titleField', 'mapPointCreator.descriptionField', 'mapPointCreator.coordinateField');
        $formDefinitionData = FormDefinitionData::fromModel($formDefinition);

        $groups = Group::all()->map(fn (Group $group): array => [
            'id' => $group->uuid,
            'name' => $group->name,
        ]);

        return Inertia::render('FormBuilder/Edit', [
            'formDefinition' => $formDefinitionData,
            'fieldTypes' => $this->activeFieldTypes(),
            'isEdit' => true,
            'groups' => $groups,
        ]);
    }

    /**
     * Store a newly created form definition.
     */
    public function store(UpsertFormDefinitionRequest $request, FormDefinitionData $formDefinitionData): RedirectResponse
    {
        $formDefinition = app(FormDefinitionService::class)->storeFormDefinitionData($formDefinitionData);

        return redirect()->route('form-definitions.edit', $formDefinition)
            ->with('success', 'Formular wurde erfolgreich erstellt.');
    }

    /**
     * Update the specified form definition.
     */
    public function update(UpsertFormDefinitionRequest $request, FormDefinition $formDefinition, FormDefinitionData $formDefinitionData): RedirectResponse
    {
        app(FormDefinitionService::class)->updateFormDefinitionData($formDefinitionData);

        return back()->with('success', 'Formular wurde erfolgreich aktualisiert.');
    }

    /**
     * Store a form definition from a template.
     */
    public function storeFromTemplate(StoreFormDefinitionFromTemplateRequest $request): RedirectResponse
    {
        $formDefinition = app(FormDefinitionService::class)->createFromTemplate(
            $request->input('template_type'),
            $request->input('group_id')
        );

        return redirect()->route('form-definitions.edit', $formDefinition->uuid)
            ->with('success', 'Formular wurde erfolgreich aus Vorlage erstellt.');
    }

    /**
     * Remove the specified form definition.
     */
    public function destroy(FormDefinition $formDefinition): RedirectResponse
    {
        $formDefinition->delete();

        return redirect()->route('form-definitions.index')
            ->with('success', 'Formular wurde erfolgreich gelöscht.');
    }
}
