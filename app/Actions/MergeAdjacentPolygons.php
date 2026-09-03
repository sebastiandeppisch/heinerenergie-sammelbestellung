<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\PostalCodeAreaException;
use App\ValueObjects\Coordinate;
use App\ValueObjects\Polygon;

/**
 * Merges adjacent polygons into a single outline.
 *
 * Adjacent areas coming from OpenStreetMap share their common border vertex by
 * vertex, because they are built from the very same ways. Therefore the union
 * can be calculated exactly by dropping every edge that is used by two areas
 * (an inner border) and stitching the remaining edges back into one ring.
 */
class MergeAdjacentPolygons
{
    /**
     * Number of decimals used to compare coordinates. OpenStreetMap delivers
     * seven decimals, which is roughly one centimeter.
     */
    private const int PRECISION = 7;

    /**
     * @param  array<int, Polygon>  $polygons
     *
     * @throws PostalCodeAreaException if the polygons do not form one connected area
     */
    public function __invoke(array $polygons): Polygon
    {
        $edges = $this->collectEdges($polygons);
        $boundary = $this->removeInnerEdges($edges);

        if ($boundary === []) {
            throw PostalCodeAreaException::notAdjacent();
        }

        return new Polygon($this->stitchToRing($boundary));
    }

    /**
     * Counts how often every undirected edge is used.
     *
     * @param  array<int, Polygon>  $polygons
     * @return array<string, array{count: int, from: Coordinate, to: Coordinate}>
     */
    private function collectEdges(array $polygons): array
    {
        $edges = [];

        foreach ($polygons as $polygon) {
            $vertices = $this->withoutClosingVertex($polygon->getCoordinates());
            $count = count($vertices);

            for ($i = 0; $i < $count; $i++) {
                $from = $vertices[$i];
                $to = $vertices[($i + 1) % $count];

                $fromKey = $this->key($from);
                $toKey = $this->key($to);

                if ($fromKey === $toKey) {
                    continue;
                }

                $edgeKey = $fromKey < $toKey ? $fromKey.'|'.$toKey : $toKey.'|'.$fromKey;

                $edges[$edgeKey] ??= ['count' => 0, 'from' => $from, 'to' => $to];

                $edges[$edgeKey]['count']++;
            }
        }

        return $edges;
    }

    /**
     * Keeps only the edges that belong to the outline of the merged area.
     *
     * @param  array<string, array{count: int, from: Coordinate, to: Coordinate}>  $edges
     * @return array<string, array{from: Coordinate, to: Coordinate}>
     */
    private function removeInnerEdges(array $edges): array
    {
        $boundary = [];

        foreach ($edges as $edgeKey => $edge) {
            if ($edge['count'] % 2 === 0) {
                continue;
            }

            $boundary[$edgeKey] = ['from' => $edge['from'], 'to' => $edge['to']];
        }

        return $boundary;
    }

    /**
     * Walks along the remaining edges and returns them as one closed ring.
     *
     * @param  array<string, array{from: Coordinate, to: Coordinate}>  $boundary
     * @return array<int, Coordinate>
     *
     * @throws PostalCodeAreaException if the edges form more than one ring
     */
    private function stitchToRing(array $boundary): array
    {
        /** @var array<string, Coordinate> $vertices */
        $vertices = [];
        /** @var array<string, array<int, string>> $neighbours */
        $neighbours = [];

        foreach ($boundary as $edge) {
            $fromKey = $this->key($edge['from']);
            $toKey = $this->key($edge['to']);

            $vertices[$fromKey] = $edge['from'];
            $vertices[$toKey] = $edge['to'];

            $neighbours[$fromKey][] = $toKey;
            $neighbours[$toKey][] = $fromKey;
        }

        $start = array_key_first($neighbours);
        $current = $start;
        $used = [];
        $ring = [];

        while (true) {
            $ring[] = $vertices[$current];

            $next = null;

            foreach ($neighbours[$current] as $candidate) {
                $edgeKey = $current < $candidate ? $current.'|'.$candidate : $candidate.'|'.$current;

                if (isset($used[$edgeKey])) {
                    continue;
                }

                $used[$edgeKey] = true;
                $next = $candidate;
                break;
            }

            if ($next === null) {
                break;
            }

            $current = $next;

            if ($current === $start) {
                break;
            }
        }

        // Every edge has to be part of the ring, otherwise the areas are not
        // connected or touch each other in a single point only.
        if ($current !== $start || count($used) !== count($boundary)) {
            throw PostalCodeAreaException::notAdjacent();
        }

        return $ring;
    }

    /**
     * @param  array<int, Coordinate>  $vertices
     * @return array<int, Coordinate>
     */
    private function withoutClosingVertex(array $vertices): array
    {
        $vertices = array_values($vertices);
        $count = count($vertices);

        if ($count > 1 && $this->key($vertices[0]) === $this->key($vertices[$count - 1])) {
            array_pop($vertices);
        }

        return $vertices;
    }

    private function key(Coordinate $coordinate): string
    {
        return number_format($coordinate->lat, self::PRECISION, '.', '')
            .','.number_format($coordinate->lng, self::PRECISION, '.', '');
    }
}
