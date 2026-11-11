<?php

use App\Enums\PublicConsent;
use App\HexNet\Enums\HexNetScoringMode;
use App\HexNet\Models\HexNet;
use App\HexNet\Services\HexNetGridService;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
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

/**
 * Two gardens in one cell: one with lawn and flowers, one with lawn and deadwood and counted only.
 *
 * @return array<int, MapPoint>
 */
function hexNetTwoGardens(MapPointCategory $main, MapPointCategory $category): array
{
    [$lawn, $flowers, $deadwood] = collect(['Rasen', 'Blühstreifen', 'Totholz'])
        ->map(fn (string $name): MapPointCharacteristic => MapPointCharacteristic::factory()->for($main, 'category')->create(['name' => $name]))
        ->all();

    $first = hexNetPoint($category, attributes: ['published' => true]);
    $first->characteristics()->attach([$lawn->id, $flowers->id]);
    $second = hexNetPoint($category, 49.8728 + 0.0002, 8.6512, ['published' => false, 'public_consent' => PublicConsent::REFUSED]);
    $second->characteristics()->attach([$lawn->id, $deadwood->id]);

    return [$first, $second];
}

dataset('scoring modes', [
    // mode, score and level with one garden, score and level with two gardens; medium from 2, dark from 4
    'stones' => [HexNetScoringMode::STONES, 1, 1, 2, 2],
    'measures' => [HexNetScoringMode::MEASURES, 2, 2, 4, 3],
    'distinct measures' => [HexNetScoringMode::DISTINCT_MEASURES, 2, 2, 3, 2],
]);

test('one garden scores in the unit of the scoring mode', function (HexNetScoringMode $mode, int $oneScore, int $oneLevel): void {
    $this->hexNet->update(['scoring_mode' => $mode, 'threshold_medium' => 2, 'threshold_dark' => 4]);
    [, $second] = hexNetTwoGardens($this->main, $this->category);
    $second->delete();
    $hexNet = $this->hexNet->fresh();

    $counts = $this->service->counts($hexNet);

    expect($counts)->toBe(['0:0' => $oneScore])
        ->and($hexNet->levelFor($counts['0:0']))->toBe($oneLevel);
})->with('scoring modes');

test('two gardens with different measures score in the unit of the scoring mode, counted-only ones included', function (HexNetScoringMode $mode, int $oneScore, int $oneLevel, int $twoScore, int $twoLevel): void {
    $this->hexNet->update(['scoring_mode' => $mode, 'threshold_medium' => 2, 'threshold_dark' => 4]);
    hexNetTwoGardens($this->main, $this->category);
    $hexNet = $this->hexNet->fresh();

    $counts = $this->service->counts($hexNet);

    expect($counts)->toBe(['0:0' => $twoScore])
        ->and($hexNet->levelFor($counts['0:0']))->toBe($twoLevel)
        ->and($this->service->stoneCounts($hexNet))->toBe(['0:0' => 2])
        ->and($this->service->stats($hexNet)->total_points)->toBe(2);
})->with('scoring modes');

test('measures added to a point change the score without saving the point', function (): void {
    $this->hexNet->update(['scoring_mode' => HexNetScoringMode::DISTINCT_MEASURES]);
    $lawn = MapPointCharacteristic::factory()->for($this->main, 'category')->create(['name' => 'Rasen']);
    $flowers = MapPointCharacteristic::factory()->for($this->main, 'category')->create(['name' => 'Blühstreifen']);
    $point = hexNetPoint($this->category);
    $point->characteristics()->attach($lawn->id);
    $hexNet = $this->hexNet->fresh();
    expect($this->service->counts($hexNet))->toBe(['0:0' => 1]);

    $this->travel(1)->seconds();
    $point->characteristics()->attach($flowers->id);

    expect($this->service->counts($hexNet))->toBe(['0:0' => 2]);
});

test('a garden without measures counts as stone but not as measure', function (): void {
    hexNetPoint($this->category);
    $this->hexNet->update(['scoring_mode' => HexNetScoringMode::MEASURES]);
    $hexNet = $this->hexNet->fresh();

    expect($this->service->counts($hexNet))->toBe([])
        ->and($this->service->stats($hexNet)->total_points)->toBe(1)
        ->and($this->service->stats($hexNet)->levels[1])->toBe(0);
});
