<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\MapPoint;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasPoints
{
    /**
     * @return MorphMany<MapPoint, $this>
     */
    public function points(): MorphMany
    {
        return $this->morphMany(MapPoint::class, 'pointable');
    }
}
