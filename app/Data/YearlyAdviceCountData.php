<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class YearlyAdviceCountData extends Data
{
    /**
     * @param  list<int>  $counts  Monthly counts for each of the 12 months in the series
     */
    public function __construct(
        public string $label,
        #[LiteralTypeScriptType('number[]')]
        public array $counts,
    ) {}
}
