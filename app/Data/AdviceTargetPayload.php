<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AdviceType;
use Spatie\LaravelData\Data;

/**
 * The advice a submission creates, read from the form when it was submitted.
 */
class AdviceTargetPayload extends Data
{
    public function __construct(
        public mixed $first_name,
        public mixed $last_name,
        public mixed $email,
        public mixed $phone,
        public mixed $address,
        public AdviceType $type,
        public int $group_id,
    ) {}
}
