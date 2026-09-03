<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\PostalCodeAreaException;
use App\ValueObjects\Polygon;

/**
 * Builds one consulting area out of several adjacent postal code areas.
 */
class BuildPolygonFromPostalCodes
{
    /**
     * @param  array<int, string>  $postalCodes
     *
     * @throws PostalCodeAreaException if a postal code is unknown or the areas are not adjacent
     */
    public function __invoke(array $postalCodes): Polygon
    {
        $postalCodes = array_values(array_unique(array_filter(array_map(trim(...), $postalCodes))));

        if ($postalCodes === []) {
            throw PostalCodeAreaException::notFound('');
        }

        $fetchPolygons = app(FetchPolygonsByPostalCode::class);

        $polygons = [];

        foreach ($postalCodes as $postalCode) {
            foreach ($fetchPolygons($postalCode) as $polygon) {
                $polygons[] = $polygon;
            }
        }

        return app(MergeAdjacentPolygons::class)($polygons);
    }
}
