<?php

use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('app.group_context', 'group');

    $this->root = Group::factory()->create(['name' => 'Root']);
    $this->child = Group::factory()->create(['name' => 'Child', 'parent_id' => $this->root->id]);
    $this->grandchild = Group::factory()->create(['name' => 'Grandchild', 'parent_id' => $this->child->id]);
    $this->sibling = Group::factory()->create(['name' => 'Sibling', 'parent_id' => $this->root->id]);
});

function mapPointGroupAdmin(Group $group): User
{
    $admin = User::factory()->create(['is_admin' => false]);
    $group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);

    return $admin;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validMapPointPayload(Group $group, array $overrides = []): array
{
    return [
        'title' => 'Neuer Punkt',
        'coordinate' => ['lat' => 49.8728, 'lng' => 8.6512],
        'published' => true,
        'group_id' => $group->uuid,
        ...$overrides,
    ];
}

test('a group admin sees the map points of the group and its descendants', function (string $actingGroup, array $expectedTitles): void {
    foreach (['root', 'child', 'grandchild', 'sibling'] as $groupKey) {
        MapPoint::factory()->for($this->{$groupKey})->create(['title' => $groupKey]);
    }

    $this->actingAs(mapPointGroupAdmin($this->{$actingGroup}))
        ->get(route('mappoints.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MapPoints/Index')
            ->where('mapPoints', fn (Collection $mapPoints): bool => $mapPoints->pluck('title')->sort()->values()->all() === $expectedTitles)
        );
})->with([
    'main group' => ['root', ['child', 'grandchild', 'root', 'sibling']],
    'sub group' => ['child', ['child', 'grandchild']],
    'leaf group' => ['grandchild', ['grandchild']],
]);

test('the map page hides the map points of ancestor groups', function (): void {
    $category = MapPointCategory::factory()->for($this->root)->create();
    MapPoint::factory()->for($this->root)->create(['category_id' => $category->id, 'title' => 'Root Point']);
    MapPoint::factory()->for($this->child)->create(['category_id' => $category->id, 'title' => 'Child Point']);

    $this->actingAs(mapPointGroupAdmin($this->child))
        ->get(route('map-points-map'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('MapPoints/Map')
            ->has("pointsByCategory.{$category->uuid}", 1)
            ->where("pointsByCategory.{$category->uuid}.0.title", 'Child Point')
        );
});

test('the category list contains own and inherited categories and marks inherited ones as read-only', function (): void {
    MapPointCategory::factory()->for($this->root)->create(['name' => 'Root Category']);
    MapPointCategory::factory()->for($this->child)->create(['name' => 'Child Category']);
    MapPointCategory::factory()->for($this->grandchild)->create(['name' => 'Grandchild Category']);
    MapPointCategory::factory()->for($this->sibling)->create(['name' => 'Sibling Category']);

    $this->actingAs(mapPointGroupAdmin($this->child))
        ->get(route('mappoint-categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Categories/Index')
            ->has('categories', 2)
            ->where('categories.0.name', 'Root Category')
            ->where('categories.0.can_edit', false)
            ->where('categories.1.name', 'Child Category')
            ->where('categories.1.can_edit', true)
        );
});

test('a group admin can create a map point with a category inherited from an ancestor group', function (): void {
    $rootCategory = MapPointCategory::factory()->for($this->root)->create();

    $this->actingAs(mapPointGroupAdmin($this->child))
        ->post(route('mappoints.store'), validMapPointPayload($this->child, ['category_id' => $rootCategory->uuid]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('map_points', [
        'title' => 'Neuer Punkt',
        'group_id' => $this->child->id,
        'category_id' => $rootCategory->id,
    ]);
});

test('a group admin can assign a map point to a descendant group', function (): void {
    $this->actingAs(mapPointGroupAdmin($this->child))
        ->post(route('mappoints.store'), validMapPointPayload($this->grandchild))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('map_points', ['title' => 'Neuer Punkt', 'group_id' => $this->grandchild->id]);
});

test('a group admin cannot assign a map point to a group outside their subtree', function (string $targetGroup): void {
    $this->actingAs(mapPointGroupAdmin($this->child))
        ->post(route('mappoints.store'), validMapPointPayload($this->{$targetGroup}))
        ->assertSessionHasErrors(['group_id' => 'Du darfst dieser Initiative keine Kartenpunkte zuordnen.']);

    $this->assertDatabaseMissing('map_points', ['title' => 'Neuer Punkt']);
})->with([
    'ancestor group' => 'root',
    'sibling group' => 'sibling',
]);

test('a map point cannot use a category of a group outside its hierarchy', function (): void {
    $siblingCategory = MapPointCategory::factory()->for($this->sibling)->create();

    $this->actingAs(mapPointGroupAdmin($this->child))
        ->post(route('mappoints.store'), validMapPointPayload($this->child, ['category_id' => $siblingCategory->uuid]))
        ->assertSessionHasErrors(['category_id' => 'Diese Kategorie ist für die gewählte Initiative nicht verfügbar.']);

    $this->assertDatabaseMissing('map_points', ['title' => 'Neuer Punkt']);
});

test('a group admin cannot change map points of an ancestor group', function (): void {
    $rootPoint = MapPoint::factory()->for($this->root)->create(['title' => 'Root Point']);

    $this->actingAs(mapPointGroupAdmin($this->child))
        ->put(route('mappoints.update', $rootPoint), validMapPointPayload($this->root, ['title' => 'Changed']))
        ->assertForbidden();

    expect($rootPoint->refresh()->title)->toBe('Root Point');
});

test('a group admin can change map points of a descendant group', function (): void {
    $childPoint = MapPoint::factory()->for($this->child)->create(['title' => 'Child Point']);

    $this->actingAs(mapPointGroupAdmin($this->root))
        ->put(route('mappoints.update', $childPoint), validMapPointPayload($this->child, ['title' => 'Changed']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($childPoint->refresh()->title)->toBe('Changed');
});

test('a group admin cannot change categories inherited from an ancestor group', function (): void {
    $rootCategory = MapPointCategory::factory()->for($this->root)->create(['name' => 'Root Category']);

    $this->actingAs(mapPointGroupAdmin($this->child))
        ->put(route('mappoint-categories.update', $rootCategory), ['name' => 'Changed'])
        ->assertForbidden();

    expect($rootCategory->refresh()->name)->toBe('Root Category');
});

test('a group admin cannot change map embeds of an ancestor group', function (): void {
    $category = MapPointCategory::factory()->for($this->root)->create();
    $rootEmbed = MapEmbed::factory()->for($this->root)->create(['name' => 'Root Embed']);

    $this->actingAs(mapPointGroupAdmin($this->child))
        ->put(route('map-embeds.update', $rootEmbed), [
            'name' => 'Changed',
            'category_ids' => [$category->uuid],
            'coordinate' => ['lat' => 49.8728, 'lng' => 8.6512],
            'zoom' => 15,
            'aspect_ratio_width' => 16,
            'aspect_ratio_height' => 9,
            'group_id' => $this->root->uuid,
        ])
        ->assertForbidden();

    expect($rootEmbed->refresh()->name)->toBe('Root Embed');
});

test('group members without admin rights cannot list map points', function (): void {
    $member = User::factory()->create(['is_admin' => false]);
    $this->child->users()->attach($member, ['is_admin' => false]);
    app(SessionService::class)->actAsGroup($this->child, false);

    $this->actingAs($member)
        ->get(route('mappoints.index'))
        ->assertForbidden();
});

test('group members without admin rights cannot create map points', function (): void {
    $member = User::factory()->create(['is_admin' => false]);
    $this->child->users()->attach($member, ['is_admin' => false]);
    app(SessionService::class)->actAsGroup($this->child, false);

    $this->actingAs($member)
        ->post(route('mappoints.store'), validMapPointPayload($this->child))
        ->assertForbidden();

    $this->assertDatabaseMissing('map_points', ['title' => 'Neuer Punkt']);
});

test('a system admin without a selected group sees the map points of all groups', function (): void {
    Config::set('app.group_context', 'global');
    app(SessionService::class)->actAsSystemAdmin();
    MapPoint::factory()->for($this->child)->create();
    MapPoint::factory()->for($this->sibling)->create();

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get(route('mappoints.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('mapPoints', 2));
});

test('a map point requires a group', function (): void {
    Config::set('app.group_context', 'global');
    app(SessionService::class)->actAsSystemAdmin();

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->post(route('mappoints.store'), validMapPointPayload($this->child, ['group_id' => null]))
        ->assertSessionHasErrors(['group_id']);

    $this->assertDatabaseMissing('map_points', ['title' => 'Neuer Punkt']);
});

/**
 * @param  array<int, MapPointCategory>  $categories
 * @return array<string, mixed>
 */
function validMapEmbedPayload(Group $group, array $categories): array
{
    return [
        'name' => 'Neue Einbettung',
        'category_ids' => array_map(fn (MapPointCategory $category): string => $category->uuid, $categories),
        'coordinate' => ['lat' => 49.8728, 'lng' => 8.6512],
        'zoom' => 15,
        'aspect_ratio_width' => 16,
        'aspect_ratio_height' => 9,
        'group_id' => $group->uuid,
    ];
}

test('a map embed of a main group can show the categories of its sub groups', function (): void {
    $childCategory = MapPointCategory::factory()->for($this->child)->create();
    $siblingCategory = MapPointCategory::factory()->for($this->sibling)->create();

    $this->actingAs(mapPointGroupAdmin($this->root))
        ->post(route('map-embeds.store'), validMapEmbedPayload($this->root, [$childCategory, $siblingCategory]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $mapEmbed = MapEmbed::where('name', 'Neue Einbettung')->firstOrFail();

    expect($mapEmbed->group_id)->toBe($this->root->id)
        ->and($mapEmbed->mapPointCategories->modelKeys())->toEqualCanonicalizing([$childCategory->id, $siblingCategory->id]);
});

test('a map embed cannot show the categories of a sibling group', function (): void {
    $siblingCategory = MapPointCategory::factory()->for($this->sibling)->create();

    $this->actingAs(mapPointGroupAdmin($this->child))
        ->post(route('map-embeds.store'), validMapEmbedPayload($this->child, [$siblingCategory]))
        ->assertSessionHasErrors(['category_ids' => 'Mindestens eine Kategorie ist für die gewählte Initiative nicht verfügbar.']);

    $this->assertDatabaseMissing('map_embeds', ['name' => 'Neue Einbettung']);
});

test('the map embed form offers each initiative the categories of its ancestors and descendants', function (): void {
    $rootCategory = MapPointCategory::factory()->for($this->root)->create();
    $childCategory = MapPointCategory::factory()->for($this->child)->create();
    $siblingCategory = MapPointCategory::factory()->for($this->sibling)->create();

    $sortedUuids = fn (iterable $uuids): array => collect($uuids)->sort()->values()->all();

    $this->actingAs(mapPointGroupAdmin($this->root))
        ->get(route('map-embeds.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where("mapCategoryIdsByGroup.{$this->root->uuid}", fn ($uuids) => $sortedUuids($uuids) === $sortedUuids([$rootCategory->uuid, $childCategory->uuid, $siblingCategory->uuid]))
            ->where("mapCategoryIdsByGroup.{$this->child->uuid}", fn ($uuids) => $sortedUuids($uuids) === $sortedUuids([$rootCategory->uuid, $childCategory->uuid]))
        );
});
