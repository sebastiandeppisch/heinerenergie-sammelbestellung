<?php

namespace App\Traits;

use App\Models\MapPoint;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasPoints
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\App\Models\MapPoint, $this>
     */
    public function points(): MorphMany
    {
        return $this->morphMany(MapPoint::class, 'pointable');
    }
}
