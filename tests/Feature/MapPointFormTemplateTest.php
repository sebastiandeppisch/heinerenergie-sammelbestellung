<?php

use App\Enums\FieldType;
use App\Models\FormDefinition;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AutoAttachesFormEmbedToken;

uses(RefreshDatabase::class, AutoAttachesFormEmbedToken::class);

beforeEach(function (): void {
    Config::set('app.group_context', 'group');
});

function templateGroupAdmin(Group $group): User
{
    $admin = User::factory()->create(['is_admin' => false]);
    $group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);

    return $admin;
}

test('the map point form template asks for the fields of the category and maps everything', function (): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->create(['name' => 'Photovoltaik']);
    $fields = $category->findOrCreateFormDefinition()->fields();
    $fields->create(['type' => FieldType::NUMBER, 'label' => 'PV-Leistung (kWp)', 'sort_order' => 0]);
    $kind = $fields->create(['type' => FieldType::SELECT, 'label' => 'Anlagenart', 'sort_order' => 1]);
    $kind->options()->create(['value' => 'dach', 'label' => 'Dachanlage', 'sort_order' => 0]);

    $this->actingAs(templateGroupAdmin($group))
        ->post(route('form-definitions.from-template'), ['template_type' => 'map_point', 'group_id' => $group->uuid, 'map_point_category_id' => $category->uuid])
        ->assertRedirect();

    $form = FormDefinition::where('name', 'Formular für Photovoltaik')->with('fields.options')->sole();
    expect($form->fields->pluck('label')->all())->toBe(['Titel', 'Beschreibung', 'Standort', 'PV-Leistung (kWp)', 'Anlagenart'])
        ->and($form->fields->firstWhere('label', 'Anlagenart')->options->pluck('label')->all())->toBe(['Dachanlage']);

    $formFields = $form->fields->keyBy('label');
    $this->post(route('form.submit', $form), [
        $formFields['Titel']->uuid => 'Schule',
        $formFields['Standort']->uuid => ['lat' => 49.87, 'lng' => 8.65],
        $formFields['PV-Leistung (kWp)']->uuid => '9.9',
        $formFields['Anlagenart']->uuid => 'dach',
    ])->assertSessionHasNoErrors();

    $mapPoint = MapPoint::sole();
    expect($mapPoint->category_id)->toBe($category->id)
        ->and($mapPoint->fields()->pluck('value', 'label')->all())->toBe(['PV-Leistung (kWp)' => 9.9, 'Anlagenart' => 'dach']);
});

test('the map point form template refuses a category of another initiative', function (): void {
    $group = Group::factory()->create();
    $foreign = MapPointCategory::factory()->create();

    $this->actingAs(templateGroupAdmin($group))
        ->post(route('form-definitions.from-template'), ['template_type' => 'map_point', 'group_id' => $group->uuid, 'map_point_category_id' => $foreign->uuid])
        ->assertSessionHasErrors(['map_point_category_id' => 'Diese Kategorie ist für die gewählte Initiative nicht verfügbar.']);

    $this->assertDatabaseCount('form_definitions', 0);
});

test('a form can only be created from a template in an initiative the user administers', function (): void {
    $otherGroup = Group::factory()->create();

    $this->actingAs(templateGroupAdmin(Group::factory()->create()))
        ->post(route('form-definitions.from-template'), ['template_type' => 'map_point', 'group_id' => $otherGroup->uuid])
        ->assertForbidden();

    $this->assertDatabaseCount('form_definitions', 0);
});

test('the map point form template lets people check the characteristics of the category and asks for their fields', function (): void {
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create(['name' => 'Garten']);
    $meadow = MapPointCategory::factory()->childOf($garden)->create(['name' => 'Wiese']);
    $hedgehogGate = MapPointCharacteristic::factory()->for($garden, 'category')->create(['name' => 'Igeltor', 'sort_order' => 0]);
    MapPointCharacteristic::factory()->for($meadow, 'category')->create(['name' => 'Blühstreifen', 'sort_order' => 1]);
    $hedgehogGate->findOrCreateFormDefinition()->fields()->create(['type' => FieldType::NUMBER, 'label' => 'Breite (cm)', 'sort_order' => 0]);

    $this->actingAs(templateGroupAdmin($group))
        ->post(route('form-definitions.from-template'), ['template_type' => 'map_point', 'group_id' => $group->uuid, 'map_point_category_id' => $meadow->uuid])
        ->assertRedirect();

    $form = FormDefinition::where('name', 'Formular für Wiese')->with('fields.options')->sole();
    expect($form->fields->pluck('label')->all())->toBe(['Titel', 'Beschreibung', 'Standort', 'Maßnahmen', 'Igeltor: Breite (cm)'])
        ->and($form->fields->firstWhere('label', 'Maßnahmen')->options->pluck('label')->all())->toBe(['Igeltor', 'Blühstreifen']);

    $formFields = $form->fields->keyBy('label');
    $this->post(route('form.submit', $form), [
        $formFields['Titel']->uuid => 'Garten am Bach',
        $formFields['Standort']->uuid => ['lat' => 49.87, 'lng' => 8.65],
        $formFields['Maßnahmen']->uuid => [$hedgehogGate->uuid],
        $formFields['Igeltor: Breite (cm)']->uuid => '13',
    ])->assertSessionHasNoErrors();

    $mapPoint = MapPoint::sole();
    expect($mapPoint->characteristics()->pluck('name')->all())->toBe(['Igeltor'])
        ->and($mapPoint->fields()->pluck('value', 'label')->all())->toBe(['Breite (cm)' => 13]);
});
