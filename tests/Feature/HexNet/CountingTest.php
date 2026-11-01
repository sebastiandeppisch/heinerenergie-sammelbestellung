<?php

use App\Enums\PublicConsent;
use App\HexNet\Models\HexNet;
use App\HexNet\Services\HexNetGridService;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->group = Group::factory()->create();
    $this->main = MapPointCategory::create(['group_id' => $this->group->id, 'name' => 'Trittsteine']);
    $this->category = MapPointCategory::create(['group_id' => $this->group->id, 'parent_id' => $this->main->id, 'name' => 'Trittsteine 2027']);
    $this->hexNet = HexNet::factory()->active()->withPlayArea()->create(['group_id' => $this->group->id]);
    $this->hexNet->categories()->sync([$this->category->id]);
    $this->service = app(HexNetGridService::class);
});

/**
 * @param  array<string, mixed>  $attributes
 */
function hexNetPoint(MapPointCategory $category, float $lat = 49.8728, float $lng = 8.6512, array $attributes = []): MapPoint
{
    return MapPoint::factory()->create([
        'group_id' => $category->group_id,
        'category_id' => $category->id,
        'lat' => $lat,
        'lng' => $lng,
        ...$attributes,
    ]);
}

test('points in the play area count in their cell regardless of published and consent', function (): void {
    hexNetPoint($this->category, attributes: ['published' => true]);
    hexNetPoint($this->category, attributes: ['published' => false]);
    hexNetPoint($this->category, attributes: ['published' => true, 'public_consent' => PublicConsent::REFUSED]);

    expect($this->service->counts($this->hexNet))->toBe(['0:0' => 3]);
});

test('points outside the play area do not count', function (): void {
    hexNetPoint($this->category, 49.95, 8.6512);

    expect($this->service->counts($this->hexNet))->toBe([]);
});

test('points of subcategories count', function (): void {
    $sub = MapPointCategory::create(['group_id' => $this->group->id, 'parent_id' => $this->category->id, 'name' => 'Darmstadt-Nord']);
    hexNetPoint($sub);

    expect(array_sum($this->service->counts($this->hexNet)))->toBe(1);
});

test('points of other categories do not count', function (): void {
    hexNetPoint($this->main);
    hexNetPoint(MapPointCategory::create(['group_id' => $this->group->id, 'name' => 'Solar']));

    expect($this->service->counts($this->hexNet))->toBe([]);
});

test('counts are invalidated when a point changes', function (): void {
    expect($this->service->counts($this->hexNet))->toBe([]);

    $point = hexNetPoint($this->category);
    expect(array_sum($this->service->counts($this->hexNet)))->toBe(1);

    $point->delete();
    expect($this->service->counts($this->hexNet))->toBe([]);
});

test('counts are invalidated when the configuration changes', function (): void {
    hexNetPoint($this->category);
    hexNetPoint($this->category, 49.8728 + 0.003, 8.6512);
    expect($this->service->counts($this->hexNet))->toHaveCount(2);

    $this->hexNet->update(['hex_radius_m' => 1000]);
    expect($this->service->counts($this->hexNet->fresh()))->toBe(['0:0' => 2]);

    $this->hexNet->categories()->sync([$this->main->id]);
    $this->hexNet->touch();
    expect(array_sum($this->service->counts($this->hexNet->fresh())))->toBe(2);
});

test('stats use the thresholds', function (): void {
    foreach (range(1, 3) as $i) {
        hexNetPoint($this->category);
    }
    hexNetPoint($this->category, 49.8728 + 0.003, 8.6512);

    $stats = $this->service->stats($this->hexNet);

    expect($stats->levels[3])->toBe(1)
        ->and($stats->levels[1])->toBe(1)
        ->and($stats->total_points)->toBe(4)
        ->and($stats->levels[0])->toBe($stats->playable_cells - 2)
        ->and($stats->percent)->toBe((int) round(200 / $stats->playable_cells));
});
