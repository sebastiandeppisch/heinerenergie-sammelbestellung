<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\FormDefinitionToMapPointCharacteristic;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Points of submissions with this option checked get the characteristic.
 */
#[TypeScript]
class FormToMapPointCharacteristicData extends Data
{
    public function __construct(
        public string $option_value,
        public string $characteristic_id,
    ) {}

    public static function fromModel(FormDefinitionToMapPointCharacteristic $model): self
    {
        return new self(
            option_value: $model->option_value,
            characteristic_id: $model->characteristic->uuid,
        );
    }
}
