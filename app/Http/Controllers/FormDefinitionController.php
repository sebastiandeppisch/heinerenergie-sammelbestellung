<?php

namespace App\Http\Controllers;

use App\Data\FormDefinitionData;
use App\Enums\FieldType;
use App\Http\Requests\UpsertFormDefinitionRequest;
use App\Models\FormDefinition;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FormDefinitionController extends Controller
{
    /**
     * Display a listing of the form definitions.
     */
    public function index()
    {
        $formDefinitions = FormDefinition::all()->map(fn ($formDefinition) => FormDefinitionData::fromModel($formDefinition));

        return Inertia::render('FormBuilder/Index', [
            'formDefinitions' => $formDefinitions
        ]);
    }

    /**
     * Show the form for creating a new form definition.
     */
    public function create()
    {
        return Inertia::render('FormBuilder/Edit', [
            'formDefinition' => null,
            'fieldTypes' => FieldType::cases()
        ]);
    }

    /**
     * Show the form for editing the specified form definition.
     */
    public function edit(FormDefinition $formDefinition)
    {
        $formDefinition->load('fields.options');
        $formDefinitionData = FormDefinitionData::from($formDefinition);

        return Inertia::render('FormBuilder/Edit', [
            'formDefinition' => $formDefinitionData,
            'fieldTypes' => FieldType::cases()
        ]);
    }

    /**
     * Store a newly created form definition.
     */
    public function store(UpsertFormDefinitionRequest $request)
    {
        DB::beginTransaction();

        try {
            // Formular erstellen
            $formDefinition = FormDefinition::create([
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->input('is_active', true),
            ]);

            // Felder und Optionen erstellen (wenn vorhanden)
            if ($request->has('fields')) {
                $this->saveFormFields($formDefinition, $request->fields);
            }

            DB::commit();

            return redirect()->route('form-definitions.edit', $formDefinition)
                ->with('success', 'Formular wurde erfolgreich erstellt.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Fehler beim Erstellen des Formulars: ' . $e->getMessage()]);
        }
    }

    /**
     * Update the specified form definition.
     */
    public function update(UpsertFormDefinitionRequest $request, FormDefinition $formDefinition)
    {

        DB::beginTransaction();

        try {
            $formDefinition->update([
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->input('is_active', true),
            ]);

            //TODO don't delete all fields, but only those that are not in the request
            $formDefinition->fields()->delete();

            if ($request->has('fields')) {
                $this->saveFormFields($formDefinition, $request->fields);
            }

            DB::commit();

            return back()->with('success', 'Formular wurde erfolgreich aktualisiert.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Fehler beim Aktualisieren des Formulars: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified form definition.
     */
    public function destroy(FormDefinition $formDefinition)
    {
        $formDefinition->delete();

        return redirect()->route('form-definitions.index')
            ->with('success', 'Formular wurde erfolgreich gelöscht.');
    }

    private function saveFormFields(FormDefinition $formDefinition, array $fields): void
    {
        foreach ($fields as $index => $fieldData) {
            $field = $formDefinition->fields()->create([
                'type' => $fieldData['type'],
                'name' => $fieldData['name'],
                'label' => $fieldData['label'],
                'placeholder' => $fieldData['placeholder'] ?? null,
                'help_text' => $fieldData['help_text'] ?? null,
                'required' => $fieldData['required'] ?? false,
                'default_value' => $fieldData['default_value'] ?? null,
                'sort_order' => $index,
                'min_length' => $fieldData['min_length'] ?? null,
                'max_length' => $fieldData['max_length'] ?? null,
                'min_value' => $fieldData['min_value'] ?? null,
                'max_value' => $fieldData['max_value'] ?? null,
                'accepted_file_types' => $fieldData['accepted_file_types'] ?? null,
            ]);

            if (isset($fieldData['options']) && is_array($fieldData['options'])) {
                foreach ($fieldData['options'] as $optIndex => $option) {
                    $field->options()->create([
                        'label' => $option['label'],
                        'value' => $option['value'],
                        'sort_order' => $optIndex,
                        'is_default' => $option['is_default'] ?? false,
                    ]);
                }
            }
        }
    }

}
