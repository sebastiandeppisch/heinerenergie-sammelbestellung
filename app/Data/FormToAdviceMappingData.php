<?php

namespace App\Data;

use App\Models\FormDefinitionToAdvice;
use App\Models\Group;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FormToAdviceMappingData extends Data
{
    public function __construct(
        public bool $enabled,
        public ?string $first_name_field_id = null,
        public ?string $last_name_field_id = null,
        public ?string $address_field_id = null,
        public ?string $email_field_id = null,
        public ?string $phone_field_id = null,
        public ?string $advice_type_field_id = null,
        public ?string $advice_type_direct = null,
        public ?string $advice_type_home_option_value = null,
        public ?string $advice_type_virtual_option_value = null,
        public ?string $default_group_id = null,
    ) {}

    public static function fromModel(?FormDefinitionToAdvice $model): self
    {
        if ($model === null) {
            return new self(enabled: false);
        }

        $model->loadMissing(['firstNameField', 'lastNameField', 'addressField', 'emailField', 'phoneField', 'adviceTypeField']);

        return new self(
            enabled: true,
            first_name_field_id: $model->firstNameField?->uuid,
            last_name_field_id: $model->lastNameField?->uuid,
            address_field_id: $model->addressField?->uuid,
            email_field_id: $model->emailField?->uuid,
            phone_field_id: $model->phoneField?->uuid,
            advice_type_field_id: $model->adviceTypeField?->uuid,
            advice_type_direct: $model->advice_type_direct,
            advice_type_home_option_value: $model->advice_type_home_option_value,
            advice_type_virtual_option_value: $model->advice_type_virtual_option_value,
            default_group_id: optional(Group::find($model->default_group_id))->uuid,
        );
    }
}
