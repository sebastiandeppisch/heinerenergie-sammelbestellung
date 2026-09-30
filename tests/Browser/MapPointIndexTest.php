<?php

use App\Enums\FieldType;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create();
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->user);
});

test('many map points scroll inside the table instead of the page', function (): void {
    MapPoint::factory()->count(40)->for($this->group)->create();

    $page = visit(route('mappoints.index'))
        ->assertNoJavaScriptErrors();

    assertFillsViewport($page, '"[data-test=mappoints-root]"');

    $table = $page->script(
        '(() => {
            const container = document.querySelector("[data-test=mappoints-root] [data-slot=table-container]");
            return { scrollHeight: container.scrollHeight, clientHeight: container.clientHeight };
        })()'
    );

    expect($table['scrollHeight'])->toBeGreaterThan($table['clientHeight']);
});

test('the detail row shows internal and former field values', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->create();
    $formDefinition = $category->findOrCreateFormDefinition();
    $phone = $formDefinition->fields()->create(['type' => FieldType::PHONE, 'label' => 'Telefon', 'sort_order' => 0]);
    $removed = $formDefinition->fields()->create(['type' => FieldType::TEXT, 'label' => 'Altes Feld', 'sort_order' => 1]);
    $mapPoint = MapPoint::factory()->for($this->group)->create(['category_id' => $category->id]);
    $phone->createMapPointField($mapPoint, '06151 123456');
    $removed->createMapPointField($mapPoint, 'alter Wert');
    $removed->delete();

    visit(route('mappoints.index'))
        ->assertDontSee('06151 123456')
        ->click('[data-test=toggle-point-details]')
        ->assertSee('06151 123456')
        ->assertSee('Frühere Angaben')
        ->assertSee('alter Wert')
        ->assertNoJavaScriptErrors();
});

test('the popup of the backend map shows public and internal field values', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->withoutImage()->create();
    $formDefinition = $category->findOrCreateFormDefinition();
    $power = $formDefinition->fields()->create(['type' => FieldType::NUMBER, 'label' => 'PV-Leistung (kWp)', 'sort_order' => 0]);
    $phone = $formDefinition->fields()->create(['type' => FieldType::PHONE, 'label' => 'Telefon', 'sort_order' => 1]);
    $category->publicFields()->sync([$power->id]);
    // The backend map opens at this position, so the marker can be clicked.
    $mapPoint = MapPoint::factory()->for($this->group)->create(['lat' => 49.8728, 'lng' => 8.6512, 'category_id' => $category->id]);
    $power->createMapPointField($mapPoint, 9.9);
    $phone->createMapPointField($mapPoint, '06151 123456');

    visit(route('map-points-map'))
        ->click('.leaflet-marker-icon')
        ->assertSeeIn('.leaflet-popup-content', '9,9')
        ->assertSeeIn('.leaflet-popup-content', '06151 123456')
        ->assertPresent('.leaflet-popup-content [aria-label="intern"]')
        ->assertNoJavaScriptErrors();
});
