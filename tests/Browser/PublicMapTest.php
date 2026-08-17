<?php

use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public map page does not overflow the viewport height', function () {
    $category = MapPointCategory::factory()->withoutImage()->create();
    MapPoint::factory()->create(['published' => true, 'category_id' => $category->id]);

    $mapEmbed = MapEmbed::factory()->create();
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    $page = visit(route('map.public', $mapEmbed))
        ->assertNoJavaScriptErrors();

    $heights = $page->script(
        '({scrollHeight: document.documentElement.scrollHeight, innerHeight: window.innerHeight})'
    );

    expect($heights['scrollHeight'])
        ->toBeLessThanOrEqual($heights['innerHeight']);
});

test('toggling a category checkbox on the public map causes no javascript errors', function () {
    $categoryA = MapPointCategory::factory()->withoutImage()->create(['name' => 'Ladesäulen']);
    $categoryB = MapPointCategory::factory()->withoutImage()->create(['name' => 'Beratungsstellen']);
    MapPoint::factory()->create(['published' => true, 'category_id' => $categoryA->id]);
    MapPoint::factory()->create(['published' => true, 'category_id' => $categoryB->id]);

    $mapEmbed = MapEmbed::factory()->create();
    $mapEmbed->mapPointCategories()->sync([$categoryA->id, $categoryB->id]);

    visit(route('map.public', $mapEmbed))
        ->assertNoJavaScriptErrors()
        ->click('Ladesäulen')
        ->assertNoJavaScriptErrors()
        ->click('Ladesäulen')
        ->assertNoJavaScriptErrors();
});

test('switching to the table tab and searching causes no javascript errors', function () {
    $category = MapPointCategory::factory()->withoutImage()->create(['name' => 'Ladesäulen']);
    MapPoint::factory()->create([
        'published' => true,
        'category_id' => $category->id,
        'title' => 'Solaranlage Nord',
        'location' => 'Musterstraße 1, 64283 Darmstadt',
    ]);

    $mapEmbed = MapEmbed::factory()->create();
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

test('the table tab is hidden when disabled for the embed', function () {
    $category = MapPointCategory::factory()->withoutImage()->create();
    MapPoint::factory()->create(['published' => true, 'category_id' => $category->id]);

    $mapEmbed = MapEmbed::factory()->create(['show_table' => false]);
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    visit(route('map.public', $mapEmbed))
        ->assertNoJavaScriptErrors()
        ->assertDontSee('Tabelle');
});
