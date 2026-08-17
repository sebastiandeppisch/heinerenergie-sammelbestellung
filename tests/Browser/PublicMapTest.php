<?php

use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public map page does not overflow the viewport height', function () {
    $category = MapPointCategory::factory()->create();
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
    $categoryA = MapPointCategory::factory()->create(['name' => 'Ladesäulen']);
    $categoryB = MapPointCategory::factory()->create(['name' => 'Beratungsstellen']);
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
