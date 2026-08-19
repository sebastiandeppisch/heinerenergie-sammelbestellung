<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\MapPointCategoryData;
use App\Http\Requests\UpsertMapPointsCategoryRequest;
use App\Models\MapPointCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MapPointCategoryController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', MapPointCategory::class);
        $categories = MapPointCategory::withCount('mapPoints')->get()
            ->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel($category));

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', MapPointCategory::class);

        return Inertia::render('Categories/Upsert');
    }

    public function store(UpsertMapPointsCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', MapPointCategory::class);
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('categories', 'public');
        }

        unset($data['image']);

        $category = MapPointCategory::create($data);

        return redirect()->route('mappoint-categories.edit', $category)->with('success', 'Die Kategorie wurde erstellt');
    }

    public function edit(MapPointCategory $mappointCategory): Response
    {
        $this->authorize('update', $mappointCategory);

        return Inertia::render('Categories/Upsert', [
            'category' => MapPointCategoryData::fromModel($mappointCategory),
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
}
