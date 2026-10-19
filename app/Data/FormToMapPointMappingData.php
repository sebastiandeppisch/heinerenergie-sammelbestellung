<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\FormDefinitionToMapPoint;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FormToMapPointMappingData extends Data
{
    public function __construct(
        public bool $enabled,
        public ?string $title_field_id = null,
        public ?string $description_field_id = null,
        public ?string $coordinate_field_id = null,
        public ?string $contact_email_field_id = null,
        public ?string $contact_enabled_field_id = null,
    ) {}

    public static function fromModel(?FormDefinitionToMapPoint $model): self
    {
        if ($model === null) {
            return new self(enabled: false);
        }

        $model->loadMissing(['titleField', 'descriptionField', 'coordinateField', 'contactEmailField', 'contactEnabledField']);

        return new self(
            enabled: true,
            title_field_id: $model->titleField?->uuid,
            description_field_id: $model->descriptionField?->uuid,
            coordinate_field_id: $model->coordinateField?->uuid,
            contact_email_field_id: $model->contactEmailField?->uuid,
            contact_enabled_field_id: $model->contactEnabledField?->uuid,
        );
    }
}
