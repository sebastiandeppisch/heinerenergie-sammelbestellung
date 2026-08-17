<?php

use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public map route is accessible without authentication', function () {
    $category = MapPointCategory::factory()->create();
    $mapEmbed = MapEmbed::factory()->create();
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    $response = $this->get(route('map.public', $mapEmbed));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('MapPoints/PublicMap'));
});

test('public map only shows published points from the embed categories', function () {
    $includedCategory = MapPointCategory::factory()->create();
    $otherCategory = MapPointCategory::factory()->create();

    $visiblePoint = MapPoint::factory()->create([
        'published' => true,
        'category_id' => $includedCategory->id,
        'title' => 'Visible Point',
    ]);

    MapPoint::factory()->create([
        'published' => false,
        'category_id' => $includedCategory->id,
        'title' => 'Unpublished Point',
    ]);

    MapPoint::factory()->create([
        'published' => true,
        'category_id' => $otherCategory->id,
        'title' => 'Other Category Point',
    ]);

    $mapEmbed = MapEmbed::factory()->create();
    $mapEmbed->mapPointCategories()->sync([$includedCategory->id]);

    $response = $this->get(route('map.public', $mapEmbed));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('MapPoints/PublicMap')
        ->has('categories', 1)
        ->has("pointsByCategory.{$includedCategory->uuid}", 1)
        ->where("pointsByCategory.{$includedCategory->uuid}.0.title", $visiblePoint->title)
        ->missing("pointsByCategory.{$otherCategory->uuid}")
    );
});

test('public map route returns 404 for unknown embed', function () {
    $response = $this->get('/map/does-not-exist');

    $response->assertStatus(404);
});

test('public map response contains the embed center and zoom', function () {
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
