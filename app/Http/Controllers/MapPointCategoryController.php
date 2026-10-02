<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\GroupBaseData;
use App\Data\MapPointCategoryData;
use App\Data\MapPointCategoryFieldData;
use App\Data\MapPointCharacteristicData;
use App\Http\Requests\UpsertMapPointsCategoryRequest;
use App\Models\FormField;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use App\Services\MapPointCharacteristicService;
use App\Services\MapPointVisibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MapPointCategoryController extends Controller
{
    /**
     * Lists the categories usable in the current group, including the read-only ones
     * inherited from ancestor groups.
     */
    public function index(Request $request, MapPointVisibilityService $visibility): Response
    {
        $this->authorize('viewAny', MapPointCategory::class);

        $tree = MapPointCategory::tree();
        $pointCounts = MapPoint::query()->whereNotNull('category_id')->groupBy('category_id')->selectRaw('category_id, count(*) as aggregate')->pluck('aggregate', 'category_id');
        $categories = $visibility->usableCategories()->with('group')->withCount(['mapPoints', 'characteristics'])->get()
            ->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel(
                $category,
                canEdit: $request->user()->can('update', $category),
                tree: $tree,
                mapPointsWithSubcategoriesCount: (int) collect($tree->subtreeIds([$category->id]))->sum(fn (int $id): int => (int) ($pointCounts[$id] ?? 0)),
            ));

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(MapPointVisibilityService $visibility): Response
    {
        $this->authorize('create', MapPointCategory::class);

        return Inertia::render('Categories/Upsert', $this->formProps($visibility));
    }

    public function store(UpsertMapPointsCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', MapPointCategory::class);
        $data = $request->getData();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('categories', 'public');
        }

        $category = MapPointCategory::create($data);

        return redirect()->route('mappoint-categories.edit', $category)->with('success', 'Die Kategorie wurde erstellt');
    }

    public function edit(MapPointCategory $mappointCategory, MapPointVisibilityService $visibility): Response
    {
        $this->authorize('update', $mappointCategory);

        return Inertia::render('Categories/Upsert', [
            'category' => MapPointCategoryData::fromModel($mappointCategory->load('group'), canEdit: true),
            'fields' => $this->fieldData($mappointCategory),
            ...$this->characteristicProps($mappointCategory),
            ...$this->formProps($visibility),
        ]);
    }

    /**
     * Opens the form builder for the category's fields. The form definition holding them is created on first use.
     */
    public function editFields(MapPointCategory $mappointCategory): RedirectResponse
    {
        $this->authorize('update', $mappointCategory);

        return redirect()->route('form-definitions.edit', $mappointCategory->findOrCreateFormDefinition());
    }

    /**
     * Moving the category in the tree removes characteristics of its former ancestors from its points and the points of its sub categories.
     */
    public function update(UpsertMapPointsCategoryRequest $request, MapPointCategory $mappointCategory, MapPointCharacteristicService $characteristicService): RedirectResponse
    {
        $this->authorize('update', $mappointCategory);
        $data = $request->getData();

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($mappointCategory->image_path) {
                Storage::disk('public')->delete($mappointCategory->image_path);
            }
            $data['image_path'] = $request->file('image')->store('categories', 'public');
        }

        DB::transaction(function () use ($mappointCategory, $data, $characteristicService): void {
            $mappointCategory->update($data);

            if ($mappointCategory->wasChanged('parent_id')) {
                $tree = MapPointCategory::tree();
                $characteristicService->detachUnselectable($tree->subtreeIds([$mappointCategory->id]), $tree);
            }
        });

        $mappointCategory->syncPublicFields(FormField::whereIn('uuid', $request->publicFieldIds())->pluck('id')->all());

        return redirect()->back()->with('success', 'Die Kategorie wurde aktualisiert');
    }

    public function destroy(MapPointCategory $mappointCategory): RedirectResponse
    {
        $this->authorize('delete', $mappointCategory);
        $name = $mappointCategory->name;

        $mappointCategory->delete();

        return redirect()->route('mappoint-categories.index')->with('info', 'Die Kategorie '.e($name).' wurde gelöscht');
    }

    /**
     * The fields of the category's points: its own, or else those of the nearest parent category with fields.
     *
     * @return array<int, MapPointCategoryFieldData>
     */
    private function fieldData(MapPointCategory $category): array
    {
        $fieldsCategoryId = MapPointCategory::tree()->fieldsCategoryId($category->id);

        if ($fieldsCategoryId === null) {
            return [];
        }

        $fieldsCategory = MapPointCategory::with('formDefinition.fields')->findOrFail($fieldsCategoryId);
        $publicFieldIds = $fieldsCategory->publicFields()->pluck('id')->all();

        return $fieldsCategory->formDefinition->fields
            ->map(fn (FormField $field): MapPointCategoryFieldData => MapPointCategoryFieldData::fromModel(
                $field,
                $fieldsCategory,
                in_array($field->id, $publicFieldIds, true),
            ))
            ->all();
    }

    /**
     * The own characteristics with their fields, and the read-only ones of the parent categories, which points of this category can have as well.
     *
     * @return array{characteristics: array<int, MapPointCharacteristicData>, inheritedCharacteristics: array<int, MapPointCharacteristicData>}
     */
    private function characteristicProps(MapPointCategory $category): array
    {
        $publicFieldIds = MapPointCategory::publicFieldIds();
        $characteristics = $category->characteristics()->with(['category', 'formDefinition.fields'])->withCount('mapPoints')->get();
        $inheritedCharacteristics = MapPointCharacteristic::query()
            ->whereIn('map_point_category_id', MapPointCategory::tree()->ancestorIds($category->id))
            ->with('category')
            ->orderBy('sort_order')
            ->get();

        return [
            'characteristics' => $characteristics->map(fn (MapPointCharacteristic $characteristic): MapPointCharacteristicData => MapPointCharacteristicData::forManagement($characteristic, $publicFieldIds))->all(),
            'inheritedCharacteristics' => $inheritedCharacteristics->map(MapPointCharacteristicData::fromModel(...))->all(),
        ];
    }

    /**
     * The parent candidates are filtered per group in the form, because a parent must be usable in the category's group.
     *
     * @return array{groups: Collection<int, GroupBaseData>, categories: Collection<int, MapPointCategoryData>, usableCategoryIdsByGroup: array<string, array<int, string>>}
     */
    private function formProps(MapPointVisibilityService $visibility): array
    {
        $groups = $visibility->selectableGroups();
        $categories = $visibility->relevantCategories()->with('group')->get();
        $tree = MapPointCategory::tree();

        return [
            'groups' => $groups->map(fn (Group $group): GroupBaseData => GroupBaseData::fromModel($group))->toBase(),
            'categories' => $categories
                ->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel($category, tree: $tree))
                ->toBase(),
            'usableCategoryIdsByGroup' => $visibility->usableCategoryIdsByGroup($groups, $categories),
        ];
    }
}
