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
