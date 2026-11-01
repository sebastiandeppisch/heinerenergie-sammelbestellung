<?php

use App\HexNet\Services\HexGrid;
use App\ValueObjects\Coordinate;
use App\ValueObjects\Polygon;

beforeEach(function (): void {
    $this->origin = new Coordinate(49.8728, 8.6512);
    $this->grid = new HexGrid($this->origin, 150);
});

test('project and unproject are inverse', function (): void {
    $coordinate = new Coordinate(49.88, 8.66);
    [$x, $y] = $this->grid->project($coordinate);
    $back = $this->grid->unproject($x, $y);

    expect($back->lat)->toEqualWithDelta($coordinate->lat, 1e-9)
        ->and($back->lng)->toEqualWithDelta($coordinate->lng, 1e-9);
});

test('the origin lies in cell 0:0', function (): void {
    expect($this->grid->cellOf($this->origin))->toBe('0:0');
});

test('the center of a cell lies in that cell', function (int $q, int $r): void {
    expect($this->grid->cellOf($this->grid->center($q, $r)))->toBe(HexGrid::id($q, $r));
})->with([[0, 0], [3, -2], [-5, 7], [12, 4], [-9, -9]]);

test('corners are one radius away from the center', function (): void {
    [$cx, $cy] = $this->grid->centerXY(2, -1);

    foreach ($this->grid->corners(2, -1) as $corner) {
        [$x, $y] = $this->grid->project($corner);
        expect(hypot($x - $cx, $y - $cy))->toEqualWithDelta(150, 1e-6);
    }
});

test('points just inside the edge stay in the cell, points beyond go to the neighbour', function (): void {
    [$cx, $cy] = $this->grid->centerXY(0, 0);
    $inradius = 150 * sqrt(3) / 2;

    expect($this->grid->cellOf($this->grid->unproject($cx + $inradius - 1, $cy)))->toBe('0:0')
        ->and($this->grid->cellOf($this->grid->unproject($cx + $inradius + 1, $cy)))->toBe('1:0');
});

test('cells in polygon roughly cover the polygon area', function (): void {
    $half = 1000;
    $polygon = new Polygon([
        $this->grid->unproject(-$half, -$half),
        $this->grid->unproject($half, -$half),
        $this->grid->unproject($half, $half),
        $this->grid->unproject(-$half, $half),
    ]);

    $cells = $this->grid->cellsInPolygon($polygon);
    $expected = (2 * $half) ** 2 / ($this->grid->cellAreaHa() * 10000);

    expect(count($cells))->toBeGreaterThan((int) ($expected * 0.85))
        ->and(count($cells))->toBeLessThan((int) ($expected * 1.15))
        ->and($cells)->toContain('0:0');

    foreach ($cells as $id) {
        expect($this->grid->contains($polygon, $this->grid->center(...HexGrid::parseId($id))))->toBeTrue();
    }
});
