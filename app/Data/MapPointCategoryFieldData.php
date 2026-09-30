<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\FieldType;
use App\Models\FormField;
use App\Models\MapPointCategory;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A field a category defines for its points, either itself or inherited from a parent category.
 */
#[TypeScript]
class MapPointCategoryFieldData extends Data
{
    public function __construct(
        public string $id,
        public string $label,
        public FieldType $type,
        public bool $is_public,
        public string $category_id,
        public string $category_name,
    ) {}

    public static function fromModel(FormField $field, MapPointCategory $owningCategory, bool $isPublic): self
    {
        return new self(
            id: $field->uuid,
            label: $field->label,
            type: $field->type,
            is_public: $isPublic,
            category_id: $owningCategory->uuid,
            category_name: $owningCategory->name,
        );
    }
}
