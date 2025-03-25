<?php

namespace App\Data;

use App\Enums\AdviceStatusResult;
use App\Models\AdviceStatus;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AdviceStatusNamesData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public AdviceStatusResult $result,
    ) {}

    public static function fromModel(AdviceStatus $status): self
    {
        return new self(
            id: $status->id,
            name: $status->name,
            result: $status->result,
        );
    }
}
