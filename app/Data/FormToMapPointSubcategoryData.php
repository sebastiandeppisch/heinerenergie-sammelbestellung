<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\FormDefinitionToMapPointSubcategory;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FormToMapPointSubcategoryData extends Data
{
    public function __construct(
        public string $option_value,
        public string $category_id,
    ) {}

    public static function fromModel(FormDefinitionToMapPointSubcategory $model): self
    {
        return new self(
            option_value: $model->option_value,
            category_id: $model->category->uuid,
        );
    }
}
