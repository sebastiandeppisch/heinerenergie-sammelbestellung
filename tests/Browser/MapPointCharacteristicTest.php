<?php

use App\Enums\FieldType;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
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

test('a characteristic is created on the category page', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->create();

    visit(route('mappoint-categories.edit', $category))
        ->assertSee('Diese Kategorie hat noch keine Maßnahmen.')
        ->click('[data-test=category-characteristics] button:has-text("Maßnahme")')
        ->fill('#characteristic_name', 'Igeltor')
        ->click('Anlegen')
        ->assertSee('Die Maßnahme wurde angelegt')
        ->assertSeeIn('[data-test=characteristic-row]', 'Igeltor')
        ->assertNoJavaScriptErrors();

    expect(MapPointCharacteristic::sole()->name)->toBe('Igeltor');
});

test('the point form offers the characteristics of the category and shows the fields of the chosen ones', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->create();
    $hedgehogGate = MapPointCharacteristic::factory()->for($category, 'category')->create(['name' => 'Igeltor']);
    $width = $hedgehogGate->findOrCreateFormDefinition()->fields()->create(['type' => FieldType::NUMBER, 'label' => 'Breite (cm)', 'sort_order' => 0]);
    $mapPoint = MapPoint::factory()->for($this->group)->create(['category_id' => $category->id]);

    visit(route('mappoints.edit', $mapPoint))
        ->assertDontSee('Felder der Maßnahme Igeltor')
        ->click("#characteristic_{$hedgehogGate->uuid}")
        ->assertSee('Felder der Maßnahme Igeltor')
        ->fill("#field_{$width->uuid}", '13')
        ->click('Kartenpunkt aktualisieren')
        ->assertSee('Der Kartenpunkt wurde aktualisiert')
        ->assertNoJavaScriptErrors();

    expect($mapPoint->characteristics()->pluck('name')->all())->toBe(['Igeltor'])
        ->and($mapPoint->fields()->value('value'))->toBe(13);
});
