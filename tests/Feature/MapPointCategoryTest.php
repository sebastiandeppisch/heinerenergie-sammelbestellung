<?php

use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
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

test('admin can view categories index', function (): void {
    $categories = MapPointCategory::factory()->count(3)->create();

    $response = $this->actingAs($this->admin)
        ->get(route('mappoint-categories.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Categories/Index')
        ->has('categories', 3)
        ->where('categories.0.name', $categories->first()->name)
    );
});

test('group admin can create a category for their group', function (): void {
    Config::set('app.group_context', 'group');

    $groupAdmin = User::factory()->create(['is_admin' => false]);
    $this->group->users()->attach($groupAdmin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);

    $response = $this->actingAs($groupAdmin)
        ->post(route('mappoint-categories.store'), [
            'name' => 'Gruppen-Kategorie',
            'group_id' => $this->group->uuid,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('map_point_categories', ['name' => 'Gruppen-Kategorie', 'group_id' => $this->group->id]);
});

test('group admin cannot create a category for a group they do not administer', function (): void {
    Config::set('app.group_context', 'group');

    $groupAdmin = User::factory()->create(['is_admin' => false]);
    $this->group->users()->attach($groupAdmin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $otherGroup = Group::factory()->create();

    $response = $this->actingAs($groupAdmin)
        ->post(route('mappoint-categories.store'), [
            'name' => 'Fremde Kategorie',
            'group_id' => $otherGroup->uuid,
        ]);

    $response->assertSessionHasErrors(['group_id' => 'Du darfst für diese Initiative keine Kategorien anlegen.']);
    $this->assertDatabaseMissing('map_point_categories', ['name' => 'Fremde Kategorie']);
});

test('group member without admin rights cannot access categories', function (): void {
    Config::set('app.group_context', 'group');

    $groupMember = User::factory()->create(['is_admin' => false]);
    $this->group->users()->attach($groupMember, ['is_admin' => false]);
    app(SessionService::class)->actAsGroup($this->group, false);

    $response = $this->actingAs($groupMember)
        ->get(route('mappoint-categories.index'));

    $response->assertStatus(403);
});

test('regular user cannot access categories', function (): void {

    $response = $this->actingAs($this->regularUser)
        ->get(route('mappoint-categories.index'));

    $response->assertStatus(403);
});

test('admin can view create category form', function (): void {
    $response = $this->actingAs($this->admin)
        ->get(route('mappoint-categories.create'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Categories/Upsert')
        ->missing('category')
        ->has('groups')
    );
});

test('admin can create category without image', function (): void {
    $categoryData = [
        'name' => 'Test Category',
        'group_id' => $this->group->uuid,
    ];

    $response = $this->actingAs($this->admin)
        ->post(route('mappoint-categories.store'), $categoryData);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('map_point_categories', [
        'name' => 'Test Category',
        'image_path' => null,
        'group_id' => $this->group->id,
    ]);
});

test('admin can create category with image', function (): void {
    Storage::fake('public');

    $image = UploadedFile::fake()->image('category-icon.jpg', 100, 100);

    $categoryData = [
        'name' => 'Test Category with Image',
        'image' => $image,
        'group_id' => $this->group->uuid,
    ];

    $response = $this->actingAs($this->admin)
        ->post(route('mappoint-categories.store'), $categoryData);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $category = MapPointCategory::where('name', 'Test Category with Image')->first();
    $this->assertNotNull($category);
    $this->assertNotNull($category->image_path);

    Storage::disk('public')->assertExists($category->image_path);
});

test('admin can view edit category form', function (): void {
    $category = MapPointCategory::factory()->create();

    $response = $this->actingAs($this->admin)
        ->get(route('mappoint-categories.edit', $category));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Categories/Upsert')
        ->has('category')
        ->where('category.name', $category->name)
    );
});

test('admin can update category', function (): void {
    $this->actingAs($this->admin);
    $category = MapPointCategory::factory()->create(['name' => 'Old Name']);

    $response = $this->put(route('mappoint-categories.update', $category), [
        'name' => 'Updated Category Name',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $category = $category->refresh();
    $this->assertEquals('Updated Category Name', $category->name);
});

test('the group of a category cannot be changed', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->create();
    $otherGroup = Group::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('mappoint-categories.update', $category), [
            'name' => $category->name,
            'group_id' => $otherGroup->uuid,
        ])
        ->assertRedirect();

    expect($category->refresh()->group_id)->toBe($this->group->id);
});

test('admin can update category with new image', function (): void {
    Storage::fake('public');

    $oldImage = UploadedFile::fake()->image('old-icon.jpg');
    $category = MapPointCategory::factory()->create([
        'image_path' => $oldImage->store('categories', 'public'),
    ]);

    $newImage = UploadedFile::fake()->image('new-icon.jpg', 100, 100);

    $updateData = [
        'name' => $category->name,
        'image' => $newImage,
    ];

    $response = $this->actingAs($this->admin)
        ->put(route('mappoint-categories.update', $category), $updateData);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $category->refresh();
    $this->assertNotNull($category->image_path);
    Storage::disk('public')->assertExists($category->image_path);
});

test('admin can delete category', function (): void {
    $category = MapPointCategory::factory()->create();

    $response = $this->actingAs($this->admin)
        ->delete(route('mappoint-categories.destroy', $category));

    $response->assertRedirect();
    $response->assertSessionHas('info');

    $this->assertDatabaseMissing('map_point_categories', ['id' => $category->id]);
});

test('admin can delete category with image', function (): void {
    Storage::fake('public');

    $image = UploadedFile::fake()->image('category-icon.jpg');
    $imagePath = $image->store('map_point_categories', 'public');

    $category = MapPointCategory::factory()->create([
        'image_path' => $imagePath,
    ]);

    $response = $this->actingAs($this->admin)
        ->delete(route('mappoint-categories.destroy', $category));

    $response->assertRedirect();
    $response->assertSessionHas('info');

    $this->assertDatabaseMissing('map_point_categories', ['id' => $category->id]);
    Storage::disk('public')->assertMissing($imagePath);
});

test('category validation works correctly', function (): void {
    $response = $this->actingAs($this->admin)
        ->post(route('mappoint-categories.store'), []);

    $response->assertSessionHasErrors(['name', 'group_id']);
});

test('image validation works correctly', function (): void {
    $response = $this->actingAs($this->admin)
        ->post(route('mappoint-categories.store'), [
            'name' => 'Test Category',
            'image' => 'not-an-image',
        ]);

    $response->assertSessionHasErrors(['image']);
});

test('categories are included in mappoints create form', function (): void {
    $categories = MapPointCategory::factory()->count(2)->create();

    $response = $this->actingAs($this->admin)
        ->get(route('mappoints.create'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('MapPoints/Upsert')
        ->has('categories', 2)
    );
});

test('categories are included in mappoints edit form', function (): void {
    $categories = MapPointCategory::factory()->count(2)->create();
    $mapPoint = MapPoint::factory()->create();

    $response = $this->actingAs($this->admin)
        ->get(route('mappoints.edit', $mapPoint));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('MapPoints/Upsert')
        ->has('categories', 2)
        ->has('mapPoint')
    );
});

test('mappoint can be created with category', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->create();

    $mapPointData = [
        'title' => 'Test MapPoint',
        'description' => 'Test description',
        'coordinate' => ['lat' => 52.5, 'lng' => 13.4],
        'published' => true,
        'group_id' => $this->group->uuid,
        'category_id' => $category->uuid,
    ];

    $response = $this->actingAs($this->admin)
        ->post(route('mappoints.store'), $mapPointData);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('map_points', [
        'title' => 'Test MapPoint',
        'group_id' => $this->group->id,
        'category_id' => $category->id,
    ]);
});

test('mappoint can be updated with different category', function (): void {
    $category1 = MapPointCategory::factory()->for($this->group)->create();
    $category2 = MapPointCategory::factory()->for($this->group)->create();

    $mapPoint = MapPoint::factory()->for($this->group)->create([
        'category_id' => $category1->id,
    ]);

    $updateData = [
        'title' => $mapPoint->title,
        'description' => $mapPoint->description,
        'coordinate' => ['lat' => 52.5, 'lng' => 13.4],
        'published' => $mapPoint->published,
        'group_id' => $this->group->uuid,
        'category_id' => $category2->uuid,
    ];

    $response = $this->actingAs($this->admin)
        ->put(route('mappoints.update', $mapPoint), $updateData);

    $response->assertRedirect();

    $mapPoint->refresh();
    $this->assertEquals($category2->id, $mapPoint->category_id);
});

test('deleting category sets mappoint category_id to null', function (): void {
    $category = MapPointCategory::factory()->create();
    $mapPoint = MapPoint::factory()->create([
        'category_id' => $category->id,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('mappoint-categories.destroy', $category))
        ->assertRedirect(route('mappoint-categories.index'));

    $mapPoint->refresh();
    $this->assertNull($mapPoint->category_id);
});

test('deleting a sub category moves its points and sub categories up to its parent', function (): void {
    $parent = MapPointCategory::factory()->create();
    $category = MapPointCategory::factory()->childOf($parent)->create();
    $subCategory = MapPointCategory::factory()->childOf($category)->create();
    $mapPoint = MapPoint::factory()->create(['category_id' => $category->id]);

    $this->actingAs($this->admin)
        ->delete(route('mappoint-categories.destroy', $category))
        ->assertRedirect(route('mappoint-categories.index'));

    $this->assertModelMissing($category);
    expect($mapPoint->refresh()->category_id)->toBe($parent->id);
    expect($subCategory->refresh()->parent_id)->toBe($parent->id);
});

test('an embed showing a deleted category keeps showing its sub categories', function (): void {
    $category = MapPointCategory::factory()->create();
    $subCategory = MapPointCategory::factory()->childOf($category)->create();
    $mapEmbed = MapEmbed::factory()->for($category->group)->create();
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    $this->actingAs($this->admin)->delete(route('mappoint-categories.destroy', $category));

    expect($mapEmbed->mapPointCategories()->pluck('map_point_categories.id')->all())->toBe([$subCategory->id]);
});

test('a sub initiative can add a sub category below a category inherited from its parent initiative', function (): void {
    $childGroup = Group::factory()->create(['parent_id' => $this->group->id]);
    $parent = MapPointCategory::factory()->for($this->group)->create();

    $this->actingAs($this->admin)
        ->post(route('mappoint-categories.store'), [
            'name' => 'Balkonkraftwerke',
            'group_id' => $childGroup->uuid,
            'parent_id' => $parent->uuid,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('map_point_categories', ['name' => 'Balkonkraftwerke', 'group_id' => $childGroup->id, 'parent_id' => $parent->id]);
});

test('a category cannot be placed below a category of a sub initiative', function (): void {
    $childGroup = Group::factory()->create(['parent_id' => $this->group->id]);
    $parent = MapPointCategory::factory()->for($childGroup)->create();

    $this->actingAs($this->admin)
        ->post(route('mappoint-categories.store'), [
            'name' => 'Photovoltaik',
            'group_id' => $this->group->uuid,
            'parent_id' => $parent->uuid,
        ])
        ->assertSessionHasErrors(['parent_id' => 'Die Oberkategorie muss zur selben oder einer übergeordneten Initiative gehören.']);

    $this->assertDatabaseMissing('map_point_categories', ['name' => 'Photovoltaik']);
});

test('a category cannot be placed below one of its own sub categories', function (): void {
    $category = MapPointCategory::factory()->create();
    $subCategory = MapPointCategory::factory()->childOf($category)->create();

    $this->actingAs($this->admin)
        ->put(route('mappoint-categories.update', $category), [
            'name' => $category->name,
            'parent_id' => $subCategory->uuid,
        ])
        ->assertSessionHasErrors(['parent_id' => 'Eine Kategorie kann nicht sich selbst oder einer ihrer Unterkategorien untergeordnet werden.']);

    expect($category->refresh()->parent_id)->toBeNull();
});

test('a category can be moved below another category and back to the top level', function (): void {
    $category = MapPointCategory::factory()->create();
    $newParent = MapPointCategory::factory()->for($category->group)->create();

    $this->actingAs($this->admin)
        ->put(route('mappoint-categories.update', $category), ['name' => $category->name, 'parent_id' => $newParent->uuid])
        ->assertSessionHasNoErrors();
    expect($category->refresh()->parent_id)->toBe($newParent->id);

    $this->actingAs($this->admin)
        ->put(route('mappoint-categories.update', $category), ['name' => $category->name, 'parent_id' => null])
        ->assertSessionHasNoErrors();
    expect($category->refresh()->parent_id)->toBeNull();
});

test('a sub category without an image uses the image of its nearest ancestor as marker', function (): void {
    $root = MapPointCategory::factory()->withImage('categories/pin-red.png')->create();
    $parent = MapPointCategory::factory()->childOf($root)->withImage('categories/pin-blue.png')->create();
    MapPointCategory::factory()->childOf($parent)->withoutImage()->create(['name' => 'Balkonkraftwerke']);

    $this->actingAs($this->admin)
        ->get(route('mappoint-categories.index'))
        ->assertInertia(fn ($page) => $page
            ->where('categories.2.name', 'Balkonkraftwerke')
            ->where('categories.2.image_path', null)
            ->where('categories.2.marker_image_path', asset('storage/categories/pin-blue.png'))
            ->where('categories.2.parent_id', $parent->uuid)
        );
});
