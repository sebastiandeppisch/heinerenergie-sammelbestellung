<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Advice;

class AdviceSaved
{
    public function __construct(
        public Advice $advice
    ) {}
}
