<?php

namespace App\Http\Controllers;

use App\Data\CategoryData;
use App\Http\Requests\UpsertCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('mapPoints')->get()
            ->map(fn (Category $category): CategoryData => CategoryData::fromModel($category));

        return Inertia::render('Categories/Index', [
            'categories' => $categories
        ]);
    }

    public function create()
    {
        return Inertia::render('Categories/Upsert');
    }

    public function store(UpsertCategoryRequest $request)
    {
        $data = $request->validated();
        
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('categories', 'public');
        }

        unset($data['image']);

        $category = Category::create($data);

        return redirect()->route('categories.edit', $category)->with('success', 'Die Kategorie wurde erstellt');
    }

    public function show(Category $category)
    {
        //
    }

    public function edit(Category $category)
    {
        return Inertia::render('Categories/Upsert', [
            'category' => CategoryData::fromModel($category)
        ]);
    }

    public function update(UpsertCategoryRequest $request, Category $category)
    {
        $data = $request->validated();
        
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            $data['image_path'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        return redirect()->back()->with('success', 'Die Kategorie wurde aktualisiert');
    }

    public function destroy(Category $category)
    {
        $name = $category->name;
        
        // Delete image if exists
        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }
        
        $category->delete();
        
        return redirect()->back()->with('info', 'Die Kategorie '.e($name).' wurde gelöscht');
    }
}
