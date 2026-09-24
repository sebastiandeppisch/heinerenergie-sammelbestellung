<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\GroupBaseData;
use App\Data\MapPointCategoryData;
use App\Http\Requests\UpsertMapPointsCategoryRequest;
use App\Models\Group;
use App\Models\MapPointCategory;
use App\Services\MapPointVisibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $categories = $visibility->usableCategories()->with('group')->withCount('mapPoints')->get()
            ->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel(
                $category,
                canEdit: $request->user()->can('update', $category),
                tree: $tree,
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
            ...$this->formProps($visibility),
        ]);
    }

    public function update(UpsertMapPointsCategoryRequest $request, MapPointCategory $mappointCategory): RedirectResponse
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

        $mappointCategory->update($data);

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
