<?php

namespace App\Traits;

use App\Models\MapPoint;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasPoints
{
    /**
     * @return MorphMany<MapPoint>
     */
    public function points(): MorphMany{
        return $this->morphMany(MapPoint::class, 'pointable');
    }
}
