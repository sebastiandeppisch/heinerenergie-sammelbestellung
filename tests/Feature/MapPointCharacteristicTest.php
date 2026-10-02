<?php

use App\Data\MapPointData;
use App\Enums\FieldType;
use App\Models\FormField;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use App\Models\MapPointField;
use App\Models\User;
use App\Services\MapPointFieldService;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('app.group_context', 'group');
});

function characteristicGroupAdmin(Group $group): User
{
    $admin = User::factory()->create(['is_admin' => false]);
    $group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);

    return $admin;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function characteristicField(MapPointCategory|MapPointCharacteristic $owner, array $attributes): FormField
{
    return $owner->findOrCreateFormDefinition()->fields()->create([
        'type' => FieldType::TEXT,
        'required' => false,
        'sort_order' => 0,
        ...$attributes,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function characteristicPointPayload(MapPoint|Group $pointOrGroup, ?MapPointCategory $category, array $overrides = []): array
{
    $group = $pointOrGroup instanceof MapPoint ? $pointOrGroup->group : $pointOrGroup;

    return [
        'title' => $pointOrGroup instanceof MapPoint ? $pointOrGroup->title : 'Garten am Bach',
        'coordinate' => ['lat' => 49.8728, 'lng' => 8.6512],
        'published' => true,
        'group_id' => $group->uuid,
        'category_id' => $category?->uuid,
        ...$overrides,
    ];
}

/**
 * The labels of the active and former values, as admins see them.
 *
 * @return array{fields: array<int, string>, former_fields: array<int, string>}
 */
function characteristicPointFieldLabels(MapPoint $mapPoint): array
{
    $data = MapPointData::fromModel($mapPoint->fresh(), onlyPublic: false);

    return [
        'fields' => $data->fields->pluck('label')->all(),
        'former_fields' => $data->former_fields->pluck('label')->all(),
    ];
}

test('a characteristic of a category can be chosen for points of its sub categories, but not for points of other categories', function (): void {
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create();
    $meadow = MapPointCategory::factory()->childOf($garden)->create();
    $solar = MapPointCategory::factory()->for($group)->create();
    $hedgehogGate = MapPointCharacteristic::factory()->for($garden, 'category')->create(['name' => 'Igeltor']);
    $admin = characteristicGroupAdmin($group);

    $this->actingAs($admin)
        ->post(route('mappoints.store'), characteristicPointPayload($group, $meadow, ['characteristic_ids' => [$hedgehogGate->uuid]]))
        ->assertSessionHasNoErrors();

    expect(MapPoint::sole()->characteristics()->pluck('name')->all())->toBe(['Igeltor']);

    $this->actingAs($admin)
        ->post(route('mappoints.store'), characteristicPointPayload($group, $solar, ['characteristic_ids' => [$hedgehogGate->uuid]]))
        ->assertSessionHasErrors('characteristic_ids');
});

test('a point has the fields of its category and of its characteristics, own fields of a sub category still replace the parent fields', function (): void {
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create();
    $meadow = MapPointCategory::factory()->childOf($garden)->create();
    characteristicField($garden, ['label' => 'Gartenfläche']);
    $meadowSize = characteristicField($meadow, ['label' => 'Wiesenfläche']);
    $deadwood = MapPointCharacteristic::factory()->for($garden, 'category')->create();
    $volume = characteristicField($deadwood, ['label' => 'Totholz (m³)']);

    $fields = app(MapPointFieldService::class)->effectiveFields($meadow->id, [$deadwood->id]);

    expect($fields->pluck('label')->all())->toBe(['Wiesenfläche', 'Totholz (m³)']);

    $this->actingAs(characteristicGroupAdmin($group))
        ->post(route('mappoints.store'), characteristicPointPayload($group, $meadow, [
            'characteristic_ids' => [$deadwood->uuid],
            'field_values' => [$meadowSize->uuid => '200', $volume->uuid => '3'],
        ]))
        ->assertSessionHasNoErrors();

    expect(MapPoint::sole()->fields()->pluck('value', 'label')->all())->toBe(['Wiesenfläche' => '200', 'Totholz (m³)' => '3']);
});

test('values of a removed characteristic become former values and are active again when it is added back', function (): void {
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create();
    $deadwood = MapPointCharacteristic::factory()->for($garden, 'category')->create();
    $volume = characteristicField($deadwood, ['label' => 'Totholz (m³)']);
    $mapPoint = MapPoint::factory()->for($group)->create(['category_id' => $garden->id]);
    $mapPoint->characteristics()->attach($deadwood);
    $volume->createMapPointField($mapPoint, '3');
    $admin = characteristicGroupAdmin($group);

    $this->actingAs($admin)
        ->put(route('mappoints.update', $mapPoint), characteristicPointPayload($mapPoint, $garden, [
            'characteristic_ids' => [],
            'field_values' => [$volume->uuid => '5'],
        ]))
        ->assertSessionHasNoErrors();

    expect(characteristicPointFieldLabels($mapPoint))->toBe(['fields' => [], 'former_fields' => ['Totholz (m³)']])
        ->and($mapPoint->fields()->sole()->value)->toBe('3');

    $this->actingAs($admin)
        ->put(route('mappoints.update', $mapPoint), characteristicPointPayload($mapPoint, $garden, ['characteristic_ids' => [$deadwood->uuid]]))
        ->assertSessionHasNoErrors();

    expect(characteristicPointFieldLabels($mapPoint))->toBe(['fields' => ['Totholz (m³)'], 'former_fields' => []]);
});

test('changing the category of a point removes the characteristics the new category does not offer', function (): void {
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create();
    $solar = MapPointCategory::factory()->for($group)->create();
    $deadwood = MapPointCharacteristic::factory()->for($garden, 'category')->create();
    $volume = characteristicField($deadwood, ['label' => 'Totholz (m³)']);
    $mapPoint = MapPoint::factory()->for($group)->create(['category_id' => $garden->id]);
    $mapPoint->characteristics()->attach($deadwood);
    $volume->createMapPointField($mapPoint, '3');
    $admin = characteristicGroupAdmin($group);

    $this->actingAs($admin)
        ->put(route('mappoints.update', $mapPoint), characteristicPointPayload($mapPoint, $solar))
        ->assertSessionHasNoErrors();

    expect($mapPoint->characteristics()->count())->toBe(0)
        ->and(characteristicPointFieldLabels($mapPoint)['former_fields'])->toBe(['Totholz (m³)']);
});

test('moving points into another category at once removes the characteristics the new category does not offer', function (): void {
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create();
    $meadow = MapPointCategory::factory()->childOf($garden)->create();
    $solar = MapPointCategory::factory()->for($group)->create();
    $deadwood = MapPointCharacteristic::factory()->for($garden, 'category')->create();
    $kept = MapPoint::factory()->for($group)->create(['category_id' => $garden->id]);
    $moved = MapPoint::factory()->for($group)->create(['category_id' => $garden->id]);
    $kept->characteristics()->attach($deadwood);
    $moved->characteristics()->attach($deadwood);
    $admin = characteristicGroupAdmin($group);

    $this->actingAs($admin)
        ->patch(route('mappoints.update-category-of-many'), ['ids' => [$kept->uuid], 'category_id' => $meadow->uuid])
        ->assertSessionHasNoErrors();
    $this->actingAs($admin)
        ->patch(route('mappoints.update-category-of-many'), ['ids' => [$moved->uuid], 'category_id' => $solar->uuid])
        ->assertSessionHasNoErrors();

    expect($kept->characteristics()->count())->toBe(1)
        ->and($moved->characteristics()->count())->toBe(0);
});

test('moving a category in the tree removes the characteristics of its former parent from its points', function (): void {
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create();
    $meadow = MapPointCategory::factory()->childOf($garden)->create();
    $deadwood = MapPointCharacteristic::factory()->for($garden, 'category')->create();
    $mapPoint = MapPoint::factory()->for($group)->create(['category_id' => $meadow->id]);
    $mapPoint->characteristics()->attach($deadwood);

    $this->actingAs(characteristicGroupAdmin($group))
        ->put(route('mappoint-categories.update', $meadow), ['name' => $meadow->name, 'parent_id' => null])
        ->assertSessionHasNoErrors();

    expect($mapPoint->characteristics()->count())->toBe(0);
});

test('deleting a characteristic removes it from the points and keeps its values as former values', function (): void {
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create();
    $deadwood = MapPointCharacteristic::factory()->for($garden, 'category')->create();
    $volume = characteristicField($deadwood, ['label' => 'Totholz (m³)']);
    $mapPoint = MapPoint::factory()->for($group)->create(['category_id' => $garden->id]);
    $mapPoint->characteristics()->attach($deadwood);
    $volume->createMapPointField($mapPoint, '3');

    $deadwood->delete();

    expect(MapPointCharacteristic::count())->toBe(0)
        ->and($mapPoint->characteristics()->count())->toBe(0)
        ->and(FormField::find($volume->id))->toBeNull()
        ->and(MapPointField::sole()->only(['form_field_id', 'label', 'value']))->toBe(['form_field_id' => null, 'label' => 'Totholz (m³)', 'value' => '3']);
});

test('deleting a category deletes its characteristics', function (): void {
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create();
    $meadow = MapPointCategory::factory()->childOf($garden)->create();
    MapPointCharacteristic::factory()->for($meadow, 'category')->create();
    $kept = MapPointCharacteristic::factory()->for($garden, 'category')->create();
    $mapPoint = MapPoint::factory()->for($group)->create(['category_id' => $meadow->id]);
    $mapPoint->characteristics()->attach(MapPointCharacteristic::pluck('id'));

    $meadow->delete();

    expect(MapPointCharacteristic::pluck('id')->all())->toBe([$kept->id])
        ->and($mapPoint->characteristics()->pluck('map_point_characteristics.id')->all())->toBe([$kept->id]);
});

test('admins of the category manage its characteristics and their public fields', function (): void {
    Storage::fake('public');
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create();
    $admin = characteristicGroupAdmin($group);

    $this->actingAs($admin)
        ->post(route('mappoint-categories.characteristics.store', $garden), [
            'name' => 'Igeltor',
            'color' => '#2e7d32',
            'icon' => UploadedFile::fake()->image('igel.png'),
        ])
        ->assertSessionHasNoErrors();

    $hedgehogGate = MapPointCharacteristic::sole();
    $width = characteristicField($hedgehogGate, ['label' => 'Breite (cm)']);
    $contact = characteristicField($hedgehogGate, ['label' => 'Kontakt']);

    $this->actingAs($admin)
        ->put(route('mappoint-characteristics.update', $hedgehogGate), [
            'name' => 'Igeltor im Zaun',
            'color' => null,
            'remove_icon' => true,
            'public_field_ids' => [$width->uuid],
        ])
        ->assertSessionHasNoErrors();

    $hedgehogGate->refresh();
    expect($hedgehogGate->only(['name', 'color', 'icon_path']))->toBe(['name' => 'Igeltor im Zaun', 'color' => null, 'icon_path' => null])
        ->and($hedgehogGate->publicFields()->pluck('id')->all())->toBe([$width->id])
        ->and(Storage::disk('public')->allFiles('characteristics'))->toBe([]);

    $this->actingAs($admin)
        ->put(route('mappoint-characteristics.update', $hedgehogGate), [
            'name' => 'Igeltor',
            'public_field_ids' => [$contact->uuid, characteristicField($garden, ['label' => 'Gartenfläche'])->uuid],
        ])
        ->assertSessionHasErrors('public_field_ids.1');
});

test('a characteristic needs a hex color', function (): void {
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create();

    $this->actingAs(characteristicGroupAdmin($group))
        ->post(route('mappoint-categories.characteristics.store', $garden), ['name' => 'Igeltor', 'color' => 'red'])
        ->assertSessionHasErrors('color');
});

test('characteristics are sorted in the given order', function (): void {
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create();
    $first = MapPointCharacteristic::factory()->for($garden, 'category')->create(['sort_order' => 0]);
    $second = MapPointCharacteristic::factory()->for($garden, 'category')->create(['sort_order' => 1]);

    $this->actingAs(characteristicGroupAdmin($group))
        ->put(route('mappoint-categories.characteristics.reorder', $garden), ['ids' => [$second->uuid, $first->uuid]])
        ->assertSessionHasNoErrors();

    expect($garden->characteristics()->pluck('id')->all())->toBe([$second->id, $first->id]);
});

test('admins of a sub initiative can assign characteristics of an inherited category, but not manage them', function (): void {
    $root = Group::factory()->create();
    $child = Group::factory()->create(['parent_id' => $root->id]);
    $garden = MapPointCategory::factory()->for($root)->create();
    $hedgehogGate = MapPointCharacteristic::factory()->for($garden, 'category')->create();
    $childAdmin = characteristicGroupAdmin($child);

    $this->actingAs($childAdmin)
        ->post(route('mappoints.store'), characteristicPointPayload($child, $garden, ['characteristic_ids' => [$hedgehogGate->uuid]]))
        ->assertSessionHasNoErrors();

    expect(MapPoint::sole()->characteristics()->count())->toBe(1);

    $this->actingAs($childAdmin)->post(route('mappoint-categories.characteristics.store', $garden), ['name' => 'Totholz'])->assertForbidden();
    $this->actingAs($childAdmin)->put(route('mappoint-characteristics.update', $hedgehogGate), ['name' => 'Totholz'])->assertForbidden();
    $this->actingAs($childAdmin)->delete(route('mappoint-characteristics.destroy', $hedgehogGate))->assertForbidden();
    $this->actingAs($childAdmin)->post(route('mappoint-characteristics.fields.edit', $hedgehogGate))->assertForbidden();
    $this->actingAs($childAdmin)->get(route('form-definitions.edit', $hedgehogGate->findOrCreateFormDefinition()))->assertForbidden();
});

test('the fields of a characteristic are edited in the form builder', function (): void {
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create(['name' => 'Garten']);
    $hedgehogGate = MapPointCharacteristic::factory()->for($garden, 'category')->create(['name' => 'Igeltor']);
    $admin = characteristicGroupAdmin($group);

    $this->actingAs($admin)
        ->post(route('mappoint-characteristics.fields.edit', $hedgehogGate))
        ->assertRedirect(route('form-definitions.edit', $hedgehogGate->fresh()->formDefinition));

    $this->actingAs($admin)
        ->get(route('form-definitions.edit', $hedgehogGate->fresh()->formDefinition))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('mapPointCategory.name', 'Garten')
            ->where('mapPointCharacteristic.name', 'Igeltor'));
});

test('deleting a characteristic through the category page', function (): void {
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create();
    $hedgehogGate = MapPointCharacteristic::factory()->for($garden, 'category')->create();

    $this->actingAs(characteristicGroupAdmin($group))
        ->delete(route('mappoint-characteristics.destroy', $hedgehogGate))
        ->assertRedirect();

    expect(MapPointCharacteristic::count())->toBe(0);
});

test('the category page lists own characteristics with their fields and the inherited ones', function (): void {
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create(['name' => 'Garten']);
    $meadow = MapPointCategory::factory()->childOf($garden)->create();
    MapPointCharacteristic::factory()->for($garden, 'category')->create(['name' => 'Igeltor']);
    $deadwood = MapPointCharacteristic::factory()->for($meadow, 'category')->create(['name' => 'Totholz']);
    characteristicField($deadwood, ['label' => 'Menge (m³)']);

    $this->actingAs(characteristicGroupAdmin($group))
        ->get(route('mappoint-categories.edit', $meadow))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('characteristics', 1)
            ->where('characteristics.0.name', 'Totholz')
            ->where('characteristics.0.fields.0.label', 'Menge (m³)')
            ->has('inheritedCharacteristics', 1)
            ->where('inheritedCharacteristics.0.name', 'Igeltor')
            ->where('inheritedCharacteristics.0.category_name', 'Garten'));
});

test('a characteristic is added to and removed from many points at once', function (): void {
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create();
    $meadow = MapPointCategory::factory()->childOf($garden)->create();
    $hedgehogGate = MapPointCharacteristic::factory()->for($garden, 'category')->create();
    $gardenPoint = MapPoint::factory()->for($group)->create(['category_id' => $garden->id]);
    $meadowPoint = MapPoint::factory()->for($group)->create(['category_id' => $meadow->id]);
    $admin = characteristicGroupAdmin($group);
    $payload = ['ids' => [$gardenPoint->uuid, $meadowPoint->uuid], 'characteristic_id' => $hedgehogGate->uuid];

    $this->actingAs($admin)->patch(route('mappoints.update-characteristic-of-many'), [...$payload, 'action' => 'add'])->assertSessionHasNoErrors();

    expect($hedgehogGate->mapPoints()->count())->toBe(2);

    $this->actingAs($admin)->patch(route('mappoints.update-characteristic-of-many'), [...$payload, 'action' => 'remove'])->assertSessionHasNoErrors();

    expect($hedgehogGate->mapPoints()->count())->toBe(0);
});

test('a characteristic can only be added at once when the categories of all points offer it', function (): void {
    $group = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($group)->create();
    $solar = MapPointCategory::factory()->for($group)->create();
    $hedgehogGate = MapPointCharacteristic::factory()->for($garden, 'category')->create();
    $gardenPoint = MapPoint::factory()->for($group)->create(['category_id' => $garden->id]);
    $solarPoint = MapPoint::factory()->for($group)->create(['category_id' => $solar->id]);

    $this->actingAs(characteristicGroupAdmin($group))
        ->patch(route('mappoints.update-characteristic-of-many'), [
            'ids' => [$gardenPoint->uuid, $solarPoint->uuid],
            'characteristic_id' => $hedgehogGate->uuid,
            'action' => 'add',
        ])
        ->assertSessionHasErrors('characteristic_id');

    expect($hedgehogGate->mapPoints()->count())->toBe(0);
});

test('points of other initiatives cannot get a characteristic at once', function (): void {
    $group = Group::factory()->create();
    $otherGroup = Group::factory()->create();
    $garden = MapPointCategory::factory()->for($otherGroup)->create();
    $hedgehogGate = MapPointCharacteristic::factory()->for($garden, 'category')->create();
    $foreignPoint = MapPoint::factory()->for($otherGroup)->create(['category_id' => $garden->id]);

    $this->actingAs(characteristicGroupAdmin($group))
        ->patch(route('mappoints.update-characteristic-of-many'), ['ids' => [$foreignPoint->uuid], 'characteristic_id' => $hedgehogGate->uuid, 'action' => 'add'])
        ->assertForbidden();
});
