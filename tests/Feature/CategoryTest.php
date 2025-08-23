<?php

use App\Models\User;
use App\Models\Group;
use App\Models\Category;
use App\Services\SessionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a system admin user
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->regularUser = User::factory()->create(['is_admin' => false]);

    $this->group = Group::create([
        'name' => 'Test Group',
        'description' => 'Test Description',
    ]);

    app(SessionService::class)->actAsGroup($this->group);
    Config::set('app.group_context', 'global');
});

test('admin can view categories index', function () {
    $categories = Category::factory()->count(3)->create();

    $response = $this->actingAs($this->admin)
        ->get(route('categories.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Categories/Index')
        ->has('categories', 3)
        ->where('categories.0.name', $categories->first()->name)
    );
});

test('regular user cannot access categories', function () {

    $response = $this->actingAs($this->regularUser)
        ->get(route('categories.index'));

    $response->assertStatus(403);
});

test('admin can view create category form', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('categories.create'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Categories/Upsert')
        ->missing('category')
    );
});

test('admin can create category without image', function () {
    $categoryData = [
        'name' => 'Test Category',
    ];

    $response = $this->actingAs($this->admin)
        ->post(route('categories.store'), $categoryData);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('categories', [
        'name' => 'Test Category',
        'image_path' => null,
    ]);
});

test('admin can create category with image', function () {
    Storage::fake('public');

    $image = UploadedFile::fake()->image('category-icon.jpg', 100, 100);
    
    $categoryData = [
        'name' => 'Test Category with Image',
        'image' => $image,
    ];

    $response = $this->actingAs($this->admin)
        ->post(route('categories.store'), $categoryData);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $category = Category::where('name', 'Test Category with Image')->first();
    $this->assertNotNull($category);
    $this->assertNotNull($category->image_path);
    
    Storage::disk('public')->assertExists($category->image_path);
});

test('admin can view edit category form', function () {
    $category = Category::factory()->create();

    $response = $this->actingAs($this->admin)
        ->get(route('categories.edit', $category));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Categories/Upsert')
        ->has('category')
        ->where('category.name', $category->name)
    );
});

test('admin can update category', function () {
    $category = Category::factory()->create(['name' => 'Old Name']);

    $updateData = [
        'name' => 'Updated Category Name',
    ];

    $response = $this->actingAs($this->admin)
        ->put(route('categories.update', $category), $updateData);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $category->refresh();
    $this->assertEquals('Updated Category Name', $category->name);
});

test('admin can update category with new image', function () {
    Storage::fake('public');
    
    $oldImage = UploadedFile::fake()->image('old-icon.jpg');
    $category = Category::factory()->create([
        'image_path' => $oldImage->store('categories', 'public'),
    ]);

    $newImage = UploadedFile::fake()->image('new-icon.jpg', 100, 100);
    
    $updateData = [
        'name' => $category->name,
        'image' => $newImage,
    ];

    $response = $this->actingAs($this->admin)
        ->put(route('categories.update', $category), $updateData);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $category->refresh();
    $this->assertNotNull($category->image_path);
    Storage::disk('public')->assertExists($category->image_path);
});

test('admin can delete category', function () {
    $category = Category::factory()->create();

    $response = $this->actingAs($this->admin)
        ->delete(route('categories.destroy', $category));

    $response->assertRedirect();
    $response->assertSessionHas('info');

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

test('admin can delete category with image', function () {
    Storage::fake('public');
    
    $image = UploadedFile::fake()->image('category-icon.jpg');
    $imagePath = $image->store('categories', 'public');
    
    $category = Category::factory()->create([
        'image_path' => $imagePath,
    ]);

    $response = $this->actingAs($this->admin)
        ->delete(route('categories.destroy', $category));

    $response->assertRedirect();
    $response->assertSessionHas('info');

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    Storage::disk('public')->assertMissing($imagePath);
});

test('category validation works correctly', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('categories.store'), []);

    $response->assertSessionHasErrors(['name']);
});

test('image validation works correctly', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('categories.store'), [
            'name' => 'Test Category',
            'image' => 'not-an-image',
        ]);

    $response->assertSessionHasErrors(['image']);
});

test('categories are included in mappoints create form', function () {
    $categories = Category::factory()->count(2)->create();

    $response = $this->actingAs($this->admin)
        ->get(route('mappoints.create'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('MapPoints/Upsert')
        ->has('categories', 2)
    );
});

test('categories are included in mappoints edit form', function () {
    $categories = Category::factory()->count(2)->create();
    $mapPoint = \App\Models\MapPoint::factory()->create();

    $response = $this->actingAs($this->admin)
        ->get(route('mappoints.edit', $mapPoint));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('MapPoints/Upsert')
        ->has('categories', 2)
        ->has('mapPoint')
    );
});

test('mappoint can be created with category', function () {
    $category = Category::factory()->create();

    $mapPointData = [
        'title' => 'Test MapPoint',
        'description' => 'Test description',
        'coordinate' => ['lat' => 52.5, 'lng' => 13.4],
        'published' => true,
        'category_id' => $category->id,
    ];

    $response = $this->actingAs($this->admin)
        ->post(route('mappoints.store'), $mapPointData);

    $response->assertRedirect();

    $this->assertDatabaseHas('map_points', [
        'title' => 'Test MapPoint',
        'category_id' => $category->id,
    ]);
});

test('mappoint can be updated with different category', function () {
    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();
    
    $mapPoint = \App\Models\MapPoint::factory()->create([
        'category_id' => $category1->id,
    ]);

    $updateData = [
        'title' => $mapPoint->title,
        'description' => $mapPoint->description,
        'coordinate' => ['lat' => 52.5, 'lng' => 13.4],
        'published' => $mapPoint->published,
        'category_id' => $category2->id,
    ];

    $response = $this->actingAs($this->admin)
        ->put(route('mappoints.update', $mapPoint), $updateData);

    $response->assertRedirect();

    $mapPoint->refresh();
    $this->assertEquals($category2->id, $mapPoint->category_id);
});

test('deleting category sets mappoint category_id to null', function () {
    $category = Category::factory()->create();
    $mapPoint = \App\Models\MapPoint::factory()->create([
        'category_id' => $category->id,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('categories.destroy', $category));

    $mapPoint->refresh();
    $this->assertNull($mapPoint->category_id);
});
