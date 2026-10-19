<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MapPointConsentCharacteristicData extends Data
{
    /**
     * @param  array<int, string>  $labels  labels of the public fields of the characteristic
     * @param  array<int, string>  $selectable_for  option values of the sub category field, '' for the category of the target
     */
    public function __construct(
        public string $option_value,
        public array $labels,
        public array $selectable_for,
    ) {}
}
