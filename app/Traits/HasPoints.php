<?php

declare(strict_types=1);

namespace App\Traits;

use App\Contracts\Pointable;
use App\Models\MapPoint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @template TPointable of Model
 *
 * @implements Pointable<TPointable>
 */
trait HasPoints
{
    /**
     * @return MorphMany<MapPoint, TPointable>
     */
    public function points(): MorphMany
    {
        /** @var MorphMany<MapPoint, TPointable> $relation */
        $relation = $this->morphMany(MapPoint::class, 'pointable');

        return $relation;
    }
}
