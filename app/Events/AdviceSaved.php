<?php

namespace App\Events;

use App\Models\Advice;

class AdviceSaved
{
    public function __construct(
        public Advice $advice
    ) {}
}
