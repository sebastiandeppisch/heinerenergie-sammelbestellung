<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\MapPoint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @template TPointable of Model
 */
interface Pointable
{
    /**
     * @return MorphMany<MapPoint, TPointable>
     */
    public function points(): MorphMany;
}
