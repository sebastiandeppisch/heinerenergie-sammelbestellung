<?php

use App\HexNet\Services\HexGrid;
use App\HexNet\Services\HexNetJitter;
use App\ValueObjects\Coordinate;

beforeEach(function (): void {
    $this->jitter = new HexNetJitter('test-key');
    $this->coordinate = new Coordinate(49.8728, 8.6512);
});

function hexNetDistanceInMeters(Coordinate $from, Coordinate $to): float
{
    [$x, $y] = new HexGrid($from, 1)->project($to);

    return hypot($x, $y);
}

test('the offset is deterministic', function (): void {
    expect($this->jitter->apply($this->coordinate, 'abc', 40))->toEqual($this->jitter->apply($this->coordinate, 'abc', 40));
});

test('the offset never exceeds the maximum', function (): void {
    foreach (range(1, 200) as $i) {
        $moved = $this->jitter->apply($this->coordinate, "uuid-$i", 40);
        expect(hexNetDistanceInMeters($this->coordinate, $moved))->toBeLessThanOrEqual(40.0001);
    }
});

test('different points move differently', function (): void {
    expect($this->jitter->offset('a', 40))->not->toEqual($this->jitter->offset('b', 40));
});

test('the offset depends on the key', function (): void {
    expect($this->jitter->offset('a', 40))->not->toEqual(new HexNetJitter('other-key')->offset('a', 40));
});

test('zero meters keeps the coordinate', function (): void {
    expect($this->jitter->apply($this->coordinate, 'abc', 0))->toBe($this->coordinate);
});
