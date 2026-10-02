<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\FormType;
use App\Models\FormDefinition;
use App\Models\FormField;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FormDefinitionData extends Data
{
    /**
     * @param  Collection<int, FormFieldData>  $fields
     * @param  list<string>|null  $allowed_embed_domains
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public bool $is_active,
        #[DataCollectionOf(FormFieldData::class)]
        public Collection $fields,
        public string $group_id,
        public ?FormToAdviceMappingData $advice_mapping = null,
        public ?FormToMapPointMappingData $map_point_mapping = null,
        public ?string $success_message = null,
        public bool $show_next_form_button = false,
        public ?string $next_form_button_text = null,
        public FormType $type = FormType::Form,
        public ?array $allowed_embed_domains = null,
        public bool $requires_email_confirmation = false,
    ) {}

    public static function fromModel(FormDefinition $model): self
    {
        $model->loadMissing('group', 'adviceCreator', 'mapPointCreator');

        return new self(
            id: $model->uuid,
            name: $model->name,
            description: $model->description,
            is_active: $model->is_active,
            type: $model->type,
            fields: $model->fields->map(fn (FormField $field): FormFieldData => FormFieldData::fromModel($field)),
            group_id: $model->group->uuid,
            advice_mapping: FormToAdviceMappingData::fromModel($model->adviceCreator),
            map_point_mapping: FormToMapPointMappingData::fromModel($model->mapPointCreator),
            success_message: $model->success_message,
            show_next_form_button: $model->show_next_form_button ?? false,
            next_form_button_text: $model->next_form_button_text,
            allowed_embed_domains: $model->allowed_embed_domains,
            requires_email_confirmation: $model->requires_email_confirmation ?? false,
        );
    }

    /**
     * Form data for the public, anonymous-facing pages. Strips the embed domain
     * whitelist, which is an internal access-control detail and must not be
     * exposed to visitors of the public form.
     */
    public static function forPublic(FormDefinition $model): self
    {
        $data = self::fromModel($model);
        $data->allowed_embed_domains = null;

        return $data;
    }
}
