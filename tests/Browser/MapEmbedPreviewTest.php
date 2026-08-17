<?php

use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->user);
});

test('the create form preview map renders without javascript errors', function () {
    $category = MapPointCategory::factory()->withoutImage()->create(['name' => 'Ladesäulen']);
    MapPoint::factory()->create(['published' => true, 'category_id' => $category->id]);

    visit(route('map-embeds.create'))
        ->assertNoJavaScriptErrors()
        ->assertSee('Vorschau')
        ->click('Ladesäulen')
        ->assertNoJavaScriptErrors();
});

test('the edit form preview map renders without javascript errors', function () {
    $category = MapPointCategory::factory()->withoutImage()->create(['name' => 'Ladesäulen']);
    MapPoint::factory()->create(['published' => true, 'category_id' => $category->id]);

    $mapEmbed = MapEmbed::factory()->create();
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    visit(route('map-embeds.edit', $mapEmbed))
        ->assertNoJavaScriptErrors()
        ->assertSee('Vorschau')
        ->click('Ladesäulen')
        ->assertNoJavaScriptErrors();
});
