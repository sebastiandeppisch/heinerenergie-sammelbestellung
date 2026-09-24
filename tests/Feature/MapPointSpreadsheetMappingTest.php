<?php

use App\Enums\MapPointSpreadsheetField;
use App\Models\Group;
use App\Models\MapPointSpreadsheetMapping;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('app.group_context', 'group');

    $this->group = Group::factory()->create();
});

function actingAsMappingAdmin(TestCase $test, Group $group): void
{
    $admin = User::factory()->create(['is_admin' => false]);
    $group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);
    $test->actingAs($admin);
}

/**
 * @return array<string, mixed>
 */
function mappingPayload(string $name): array
{
    return [
        'name' => $name,
        'key_field' => 'title',
        'columns' => [
            ['header' => 'Bezeichnung', 'field' => 'title'],
            ['header' => 'Leistung', 'field' => 'ignore'],
            ['header' => 'Y-Koordinate', 'field' => 'lat'],
            ['header' => 'X-Koordinate', 'field' => 'lng'],
        ],
    ];
}

test('a group admin can save a mapping for the current group', function (): void {
    actingAsMappingAdmin($this, $this->group);

    $this->post(route('mappoints.spreadsheet-mappings.store'), mappingPayload('Anlagenliste'))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $mapping = MapPointSpreadsheetMapping::sole();

    expect($mapping->group_id)->toBe($this->group->id)
        ->and($mapping->name)->toBe('Anlagenliste')
        ->and($mapping->columns[1])->toBe(['header' => 'Leistung', 'field' => 'ignore']);
});

test('mapping names are unique within a group but may repeat in other groups', function (): void {
    MapPointSpreadsheetMapping::factory()->for($this->group)->create(['name' => 'Anlagenliste']);
    MapPointSpreadsheetMapping::factory()->create(['name' => 'Andere Gruppe']);
    actingAsMappingAdmin($this, $this->group);

    $this->post(route('mappoints.spreadsheet-mappings.store'), mappingPayload('Anlagenliste'))
        ->assertSessionHasErrors(['name']);

    $this->post(route('mappoints.spreadsheet-mappings.store'), mappingPayload('Andere Gruppe'))
        ->assertSessionHasNoErrors();

    expect(MapPointSpreadsheetMapping::where('name', 'Andere Gruppe')->count())->toBe(2);
});

test('a group admin can update and delete a mapping of the group', function (): void {
    $mapping = MapPointSpreadsheetMapping::factory()->for($this->group)->create(['name' => 'Alt']);
    actingAsMappingAdmin($this, $this->group);

    $this->put(route('mappoints.spreadsheet-mappings.update', $mapping), mappingPayload('Neu'))
        ->assertSessionHasNoErrors();

    expect($mapping->refresh()->name)->toBe('Neu');

    $this->delete(route('mappoints.spreadsheet-mappings.destroy', $mapping))->assertRedirect();

    $this->assertModelMissing($mapping);
});

test('a group admin cannot change mappings of an ancestor group', function (): void {
    $mapping = MapPointSpreadsheetMapping::factory()->for($this->group)->create(['name' => 'Hauptgruppe']);
    actingAsMappingAdmin($this, Group::factory()->create(['parent_id' => $this->group->id]));

    $this->put(route('mappoints.spreadsheet-mappings.update', $mapping), mappingPayload('Geändert'))->assertForbidden();

    expect($mapping->refresh()->name)->toBe('Hauptgruppe');
});

test('the import page only offers the mappings of the current group', function (): void {
    Storage::fake('local');
    MapPointSpreadsheetMapping::factory()->for($this->group)->create(['name' => 'Eigene Vorlage']);
    MapPointSpreadsheetMapping::factory()->create(['name' => 'Fremde Vorlage']);
    actingAsMappingAdmin($this, $this->group);

    $this->get(route('mappoints.import.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('mappings', 1)
            ->where('mappings.0.name', 'Eigene Vorlage')
        );
});

test('group members without admin rights cannot save mappings', function (): void {
    $member = User::factory()->create(['is_admin' => false]);
    $this->group->users()->attach($member, ['is_admin' => false]);
    app(SessionService::class)->actAsGroup($this->group, false);

    $this->actingAs($member)
        ->post(route('mappoints.spreadsheet-mappings.store'), mappingPayload('Anlagenliste'))
        ->assertForbidden();

    $this->assertDatabaseCount('map_point_spreadsheet_mappings', 0);
});

test('a mapping can be saved without a key field', function (): void {
    actingAsMappingAdmin($this, $this->group);

    $this->post(route('mappoints.spreadsheet-mappings.store'), [...mappingPayload('Nur neue Punkte'), 'key_field' => 'ignore'])
        ->assertSessionHasNoErrors();

    expect(MapPointSpreadsheetMapping::sole()->key_field)->toBe(MapPointSpreadsheetField::IGNORE);
});

test('a mapping with an id column must use the id as its key field', function (): void {
    actingAsMappingAdmin($this, $this->group);

    $payload = mappingPayload('Anlagenliste mit ID');
    $payload['columns'][] = ['header' => 'ID', 'field' => 'id'];

    $this->post(route('mappoints.spreadsheet-mappings.store'), $payload)->assertSessionHasErrors(['key_field']);

    $this->assertDatabaseCount('map_point_spreadsheet_mappings', 0);
});
