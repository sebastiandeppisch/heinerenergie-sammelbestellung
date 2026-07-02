<?php

namespace App\Data;

use App\Enums\AdviceStatusResult;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class StatusNameCountData extends Data
{
    public function __construct(
        public string $name,
        public AdviceStatusResult $result,
        public int $count,
    ) {}
}
