<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * The two levels of the consent block: show the point at all, and additionally show the offered details.
 */
class ConsentChoiceData extends Data
{
    public function __construct(
        public bool $show = false,
        public bool $details = false,
    ) {}
}
