<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Freezes what the targets of a form create, so later changes to the form do not affect a waiting submission.
 */
class FormTargetPayload extends Data
{
    public function __construct(
        public ?AdviceTargetPayload $advice = null,
        public ?MapPointTargetPayload $map_point = null,
    ) {}
}
