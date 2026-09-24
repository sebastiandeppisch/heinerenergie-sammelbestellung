<?php

use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->group = Group::factory()->create();
});

test('public map page does not overflow the viewport height', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->withoutImage()->create();
    MapPoint::factory()->for($this->group)->create(['published' => true, 'category_id' => $category->id]);

    $mapEmbed = MapEmbed::factory()->for($this->group)->create();
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    $page = visit(route('map.public', $mapEmbed))
        ->assertNoJavaScriptErrors();

    $heights = $page->script(
        '({scrollHeight: document.documentElement.scrollHeight, innerHeight: window.innerHeight})'
    );

    expect($heights['scrollHeight'])
        ->toBeLessThanOrEqual($heights['innerHeight']);
});

test('toggling a category checkbox on the public map causes no javascript errors', function (): void {
    $categoryA = MapPointCategory::factory()->for($this->group)->withoutImage()->create(['name' => 'Ladesäulen']);
    $categoryB = MapPointCategory::factory()->for($this->group)->withoutImage()->create(['name' => 'Beratungsstellen']);
    MapPoint::factory()->for($this->group)->create(['published' => true, 'category_id' => $categoryA->id]);
    MapPoint::factory()->for($this->group)->create(['published' => true, 'category_id' => $categoryB->id]);

    $mapEmbed = MapEmbed::factory()->for($this->group)->create();
    $mapEmbed->mapPointCategories()->sync([$categoryA->id, $categoryB->id]);

    visit(route('map.public', $mapEmbed))
        ->assertNoJavaScriptErrors()
        ->click('Ladesäulen')
        ->assertNoJavaScriptErrors()
        ->click('Ladesäulen')
        ->assertNoJavaScriptErrors();
});

test('switching to the table tab and searching causes no javascript errors', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->withoutImage()->create(['name' => 'Ladesäulen']);
    MapPoint::factory()->for($this->group)->create([
        'published' => true,
        'category_id' => $category->id,
        'title' => 'Solaranlage Nord',
        'location' => 'Musterstraße 1, 64283 Darmstadt',
    ]);

    $mapEmbed = MapEmbed::factory()->for($this->group)->create();
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    visit(route('map.public', $mapEmbed))
        ->assertNoJavaScriptErrors()
        ->click('Tabelle')
        ->assertNoJavaScriptErrors()
        ->assertSee('Solaranlage Nord')
        ->assertSee('Musterstraße 1, 64283 Darmstadt')
        ->fill('input[placeholder="Suchen..."]', 'Solaranlage')
        ->assertNoJavaScriptErrors()
        ->assertSee('Solaranlage Nord');
});

test('the map uses the custom shadcn zoom control instead of the default leaflet one', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->withoutImage()->create();
    MapPoint::factory()->for($this->group)->create(['published' => true, 'category_id' => $category->id]);

    $mapEmbed = MapEmbed::factory()->for($this->group)->create(['zoom' => 10]);
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    visit(route('map.public', $mapEmbed))
        ->assertNoJavaScriptErrors()
        ->assertNotPresent('.leaflet-control-zoom')
        ->assertPresent('.leaflet-control')
        ->click('.leaflet-control button:first-child')
        ->assertNoJavaScriptErrors();
});

test('the table tab is hidden when disabled for the embed', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->withoutImage()->create();
    MapPoint::factory()->for($this->group)->create(['published' => true, 'category_id' => $category->id]);

    $mapEmbed = MapEmbed::factory()->for($this->group)->create(['show_table' => false]);
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    visit(route('map.public', $mapEmbed))
        ->assertNoJavaScriptErrors()
        ->assertDontSee('Tabelle');
});

test('the location text in the table links to the map and shows a map preview on hover', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->withoutImage()->create();
    MapPoint::factory()->for($this->group)->create([
        'published' => true,
        'category_id' => $category->id,
        'title' => 'Punkt mit Ort',
        'location' => 'Musterstraße 1, 64283 Darmstadt',
        'lat' => 49.87285,
        'lng' => 8.65102,
    ]);

    $mapEmbed = MapEmbed::factory()->for($this->group)->create();
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    visit(route('map.public', $mapEmbed))
        ->click('Tabelle')
        ->assertSee('Musterstraße 1, 64283 Darmstadt')
        ->assertAttribute('table a[href*="openstreetmap.org"]', 'href', 'https://www.openstreetmap.org/?mlat=49.87285&mlon=8.65102#map=15/49.87285/8.65102')
        ->hover('table a[href*="openstreetmap.org"]')
        ->assertPresent('iframe[src*="openstreetmap.org/export/embed.html"]')
        ->assertNoJavaScriptErrors();
});

test('the table shows the coordinates instead of a dash when a point has no location', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->withoutImage()->create();
    MapPoint::factory()->for($this->group)->create([
        'published' => true,
        'category_id' => $category->id,
        'title' => 'Punkt ohne Ort',
        'location' => null,
        'lat' => 49.87285,
        'lng' => 8.65102,
    ]);

    $mapEmbed = MapEmbed::factory()->for($this->group)->create();
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    visit(route('map.public', $mapEmbed))
        ->assertNoJavaScriptErrors()
        ->click('Tabelle')
        ->assertNoJavaScriptErrors()
        ->assertSee('Punkt ohne Ort')
        ->assertSee('49.87285, 8.65102')
        ->assertDontSee('–');
});

test('points of a category without an image are shown with the default marker', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->withoutImage()->create();
    $mapEmbed = MapEmbed::factory()->for($this->group)->create(['zoom' => 12]);
    MapPoint::factory()->for($this->group)->create([
        'published' => true,
        'category_id' => $category->id,
        'lat' => $mapEmbed->lat,
        'lng' => $mapEmbed->lng,
    ]);
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    visit(route('map.public', $mapEmbed))
        ->assertNoJavaScriptErrors()
        ->assertPresent('img.leaflet-marker-icon');
});
