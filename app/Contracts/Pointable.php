<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @template TPointable of Model
 */
interface Pointable
{
    public function points(): MorphMany;
}
