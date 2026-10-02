<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Context\GroupContextContract;
use App\Data\FormDefinitionData;
use App\Data\FormFieldData;
use App\Data\MapPointCategoryData;
use App\Enums\FieldType;
use App\Enums\FormType;
use App\Http\Requests\StoreFormDefinitionFromTemplateRequest;
use App\Http\Requests\UpsertFormDefinitionRequest;
use App\Models\FormDefinition;
use App\Models\Group;
use App\Models\MapPointCategory;
use App\Services\FormDefinitionService;
use App\Services\MapPointFieldService;
use App\Services\MapPointVisibilityService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormDefinitionController extends Controller
{
    /**
     * Display a listing of the form definitions.
     */
    public function index(GroupContextContract $groupContext, MapPointVisibilityService $visibility): Response
    {
        $query = FormDefinition::with(['fields', 'fields.options', 'group', 'adviceCreator', 'mapPointCreator']);

        if ($groupContext->getCurrentGroup() !== null) {
            $query = $query->where('group_id', $groupContext->getCurrentGroup()->id);
        }

        $all = $query->get();

        $formDefinitions = $all->where('type', FormType::Form)->map(fn (FormDefinition $fd): FormDefinitionData => FormDefinitionData::fromModel($fd))->values();
        $checklists = $all->where('type', FormType::Checklist)->map(fn (FormDefinition $fd): FormDefinitionData => FormDefinitionData::fromModel($fd))->values();

        $groups = Group::all()->map(fn (Group $group): array => [
            'id' => $group->uuid,
            'name' => $group->name,
        ]);

        $mapPointCategories = $visibility->relevantCategories()->with('group')->withCount('mapPoints')->get();
        $tree = MapPointCategory::tree();

        return Inertia::render('FormBuilder/Index', [
            'formDefinitions' => $formDefinitions,
            'checklists' => $checklists,
            'groups' => $groups,
            // A map point form can ask for the fields of a category usable in its initiative.
            'mapPointCategories' => $mapPointCategories->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel($category, tree: $tree))->all(),
            'usableMapPointCategoryIdsByGroup' => $visibility->usableCategoryIdsByGroup(Group::all(), $mapPointCategories),
        ]);
    }

    /**
     * Show the form for creating a new form definition.
     */
    public function create(Request $request, GroupContextContract $groupContext): Response
    {
        $groups = Group::all()->map(fn (Group $group): array => [
            'id' => $group->uuid,
            'name' => $group->name,
        ]);

        $initialType = FormType::tryFrom($request->integer('type')) ?? FormType::Form;

        return Inertia::render('FormBuilder/Edit', [
            'formDefinition' => null,
            'fieldTypes' => $this->activeFieldTypes(),
            'isEdit' => false,
            'groups' => $groups,
            'initialType' => $initialType,
            ...$this->mapPointTargetProps($groupContext->getCurrentGroup()),
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
        $formDefinition->load('fields.options', 'adviceCreator.firstNameField', 'adviceCreator.lastNameField', 'adviceCreator.addressField', 'adviceCreator.emailField', 'adviceCreator.phoneField', 'adviceCreator.adviceTypeField', 'mapPointCreator.titleField', 'mapPointCreator.descriptionField', 'mapPointCreator.coordinateField', 'mapPointCreator.category', 'mapPointCreator.subcategoryField', 'mapPointCreator.subcategories.category', 'mapPointCreator.fieldMappings.targetField', 'mapPointCreator.fieldMappings.sourceField');
        $formDefinitionData = FormDefinitionData::fromModel($formDefinition);

        $groups = Group::all()->map(fn (Group $group): array => [
            'id' => $group->uuid,
            'name' => $group->name,
        ]);

        $mapPointCategory = $formDefinition->type === FormType::MapPointFields ? $formDefinition->mapPointFieldsCategory() : null;
        $mapPointCharacteristic = $formDefinition->type === FormType::MapPointFields ? $formDefinition->mapPointCharacteristic : null;

        if ($mapPointCategory !== null) {
            $this->authorize('update', $mapPointCategory);
        }

        return Inertia::render('FormBuilder/Edit', [
            'formDefinition' => $formDefinitionData,
            'fieldTypes' => $mapPointCategory !== null ? FieldType::typesForMapPointFields : $this->activeFieldTypes(),
            'isEdit' => true,
            'groups' => $groups,
            'mapPointCategory' => $mapPointCategory === null ? null : ['id' => $mapPointCategory->uuid, 'name' => $mapPointCategory->name],
            'mapPointCharacteristic' => $mapPointCharacteristic === null ? null : ['id' => $mapPointCharacteristic->uuid, 'name' => $mapPointCharacteristic->name],
            ...$this->mapPointTargetProps($formDefinition->group),
        ]);
    }

    /**
     * The categories a form of the group can create its points in, with the fields each of them has, and which form
     * field types can fill which category field.
     *
     * @return array{mapPointCategories: array<int, MapPointCategoryData>, mapPointFieldsByCategory: array<string, array<int, FormFieldData>>, mapPointFieldSourceTypes: array<string, array<int, FieldType>>}
     */
    private function mapPointTargetProps(?Group $group): array
    {
        $categories = $group === null ? new Collection : MapPointCategory::usableInGroup($group)->with('group')->withCount('mapPoints')->get();
        $tree = MapPointCategory::tree();

        return [
            'mapPointCategories' => $categories->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel($category, tree: $tree))->all(),
            'mapPointFieldsByCategory' => array_map(
                fn (Collection $fields): array => $fields->map(FormFieldData::fromModel(...))->all(),
                app(MapPointFieldService::class)->fieldsByCategory($categories, $tree),
            ),
            'mapPointFieldSourceTypes' => collect(FieldType::typesForMapPointFields)
                ->mapWithKeys(fn (FieldType $type): array => [$type->value => $type->mapPointFieldSourceTypes()])
                ->all(),
        ];
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
            $request->input('group_id'),
            $request->mapPointCategory(),
        );

        return redirect()->route('form-definitions.edit', $formDefinition->uuid)
            ->with('success', 'Formular wurde erfolgreich aus Vorlage erstellt.');
    }

    /**
     * Remove the specified form definition.
     */
    public function destroy(FormDefinition $formDefinition): RedirectResponse
    {
        // Fields of a map point category are deleted together with the category.
        abort_if($formDefinition->type === FormType::MapPointFields, 404);

        $formDefinition->delete();

        return redirect()->route('form-definitions.index')
            ->with('success', 'Formular wurde erfolgreich gelöscht.');
    }
}
