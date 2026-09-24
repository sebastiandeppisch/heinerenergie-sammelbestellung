<?php

use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public map route is accessible without authentication', function (): void {
    $category = MapPointCategory::factory()->create();
    $mapEmbed = MapEmbed::factory()->create();
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    $response = $this->get(route('map.public', $mapEmbed));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('MapPoints/PublicMap'));
});

test('public map only shows published points from the embed categories', function (): void {
    $group = Group::factory()->create();
    $includedCategory = MapPointCategory::factory()->for($group)->create();
    $otherCategory = MapPointCategory::factory()->for($group)->create();

    $visiblePoint = MapPoint::factory()->for($group)->create([
        'published' => true,
        'category_id' => $includedCategory->id,
        'title' => 'Visible Point',
        'location' => 'Musterstraße 1, 64283 Darmstadt',
    ]);

    MapPoint::factory()->for($group)->create([
        'published' => false,
        'category_id' => $includedCategory->id,
        'title' => 'Unpublished Point',
    ]);

    MapPoint::factory()->for($group)->create([
        'published' => true,
        'category_id' => $otherCategory->id,
        'title' => 'Other Category Point',
    ]);

    $mapEmbed = MapEmbed::factory()->for($group)->create();
    $mapEmbed->mapPointCategories()->sync([$includedCategory->id]);

    $response = $this->get(route('map.public', $mapEmbed));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('MapPoints/PublicMap')
        ->has('categories', 1)
        ->has("pointsByCategory.{$includedCategory->uuid}", 1)
        ->where("pointsByCategory.{$includedCategory->uuid}.0.title", $visiblePoint->title)
        ->where("pointsByCategory.{$includedCategory->uuid}.0.location", $visiblePoint->location)
        ->missing("pointsByCategory.{$otherCategory->uuid}")
    );
});

test('public map route returns 404 for unknown embed', function (): void {
    $response = $this->get('/map/does-not-exist');

    $response->assertStatus(404);
});

test('public map response contains the embed center and zoom', function (): void {
    $category = MapPointCategory::factory()->create();
    $mapEmbed = MapEmbed::factory()->create([
        'lat' => 52.52,
        'lng' => 13.405,
        'zoom' => 12,
    ]);
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    $response = $this->get(route('map.public', $mapEmbed));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('MapPoints/PublicMap')
        ->where('center.lat', 52.52)
        ->where('center.lng', 13.405)
        ->where('zoom', 12)
    );
});

test('public map response reflects whether the table view is enabled', function (): void {
    $category = MapPointCategory::factory()->create();
    $mapEmbed = MapEmbed::factory()->create(['show_table' => false]);
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    $response = $this->get(route('map.public', $mapEmbed));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('MapPoints/PublicMap')
        ->where('showTable', false)
    );
});

test('public map of a group shows the published points of its descendant groups', function (): void {
    $group = Group::factory()->create();
    $childGroup = Group::factory()->create(['parent_id' => $group->id]);
    $category = MapPointCategory::factory()->for($group)->create();
    MapPoint::factory()->for($childGroup)->create(['published' => true, 'category_id' => $category->id, 'title' => 'Child Point']);

    $mapEmbed = MapEmbed::factory()->for($group)->create();
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    $this->get(route('map.public', $mapEmbed))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has("pointsByCategory.{$category->uuid}", 1)
            ->where("pointsByCategory.{$category->uuid}.0.title", 'Child Point')
        );
});

test('public map of a group hides the points of its ancestor groups', function (): void {
    $group = Group::factory()->create();
    $childGroup = Group::factory()->create(['parent_id' => $group->id]);
    $category = MapPointCategory::factory()->for($group)->create();
    MapPoint::factory()->for($group)->create(['published' => true, 'category_id' => $category->id, 'title' => 'Parent Point']);
    MapPoint::factory()->for($childGroup)->create(['published' => true, 'category_id' => $category->id, 'title' => 'Child Point']);

    $mapEmbed = MapEmbed::factory()->for($childGroup)->create();
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    $this->get(route('map.public', $mapEmbed))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has("pointsByCategory.{$category->uuid}", 1)
            ->where("pointsByCategory.{$category->uuid}.0.title", 'Child Point')
        );
});
