<?php

namespace App\Contracts;

use App\Models\MapPoint;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Pointable
{
    /**
     * @return MorphMany<MapPoint>
     */
    public function points(): MorphMany;

}
