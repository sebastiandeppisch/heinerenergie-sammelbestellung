<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Context\GroupContextContract;
use App\Enums\FieldType;
use App\Enums\FormType;
use App\Models\FormDefinition;
use App\Models\Group;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use App\Rules\FormFieldExistsInRequest;
use App\Rules\Hostname;
use App\Rules\MappedFormFieldMustBeRequired;
use App\Services\MapPointFieldService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Override;

class UpsertFormDefinitionRequest extends FormRequest
{
    /**
     * Forms belong to an initiative, so only its admins may change them, and only admins of the target
     * initiative may move a form there. Fields of a map point category or characteristic follow the rights on the category.
     */
    public function authorize(): bool
    {
        $existing = $this->route('form_definition');

        if ($existing instanceof FormDefinition && $existing->type === FormType::MapPointFields) {
            $category = $existing->mapPointFieldsCategory();

            return $category !== null && $this->user()->can('update', $category);
        }

        if ($existing instanceof FormDefinition && ! $this->isGroupAdmin($existing->group)) {
            return false;
        }

        // An unknown initiative is rejected by the validation rules with a readable message.
        $targetGroup = Group::where('uuid', $this->input('group_id'))->first();

        return $targetGroup === null || $this->isGroupAdmin($targetGroup);
    }

    private function isGroupAdmin(Group $group): bool
    {
        $groupContext = app(GroupContextContract::class);

        return $groupContext->isActingAsSystemAdmin($this->user())
            || $groupContext->isActingAsTransitiveAdmin($this->user(), $group);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'type' => ['nullable', Rule::enum(FormType::class)],
            'success_message' => 'nullable|string',
            'show_next_form_button' => 'boolean',
            'next_form_button_text' => 'nullable|string|max:255|required_if:show_next_form_button,true',
            'allowed_embed_domains' => 'nullable|array',
            'allowed_embed_domains.*' => ['string', 'max:255', new Hostname],
            'requires_email_confirmation' => 'boolean',

            // Felder-Array-Validierung
            'fields' => 'array',
            'fields.*.type' => ['required', Rule::enum(FieldType::class)],
            'fields.*.label' => 'required|string|max:255',
            'fields.*.placeholder' => 'nullable|string|max:255',
            'fields.*.help_text' => 'nullable|string',
            'fields.*.required' => 'boolean',
            'fields.*.default_value' => 'nullable|string',
            'fields.*.min_length' => 'nullable|integer|min:0',
            'fields.*.max_length' => 'nullable|integer|min:0',
            'fields.*.min_value' => 'nullable|numeric',
            'fields.*.max_value' => 'nullable|numeric',
            'fields.*.accepted_file_types' => 'nullable|array',

            // Optionen-Validierung für Select-, Radio- und Checkbox-Felder
            'fields.*.options' => 'array|required_if:fields.*.type,select,radio,checkbox',
            'fields.*.options.*.label' => 'required|string|max:255',
            'fields.*.options.*.value' => 'required|string|max:255',
            'fields.*.options.*.is_default' => 'required|boolean',
            'fields.*.options.*.is_required' => 'required|boolean',

            // Advice mapping (optional)
            'advice_mapping' => 'nullable|array',
            'advice_mapping.enabled' => 'boolean',

            // Map point mapping (optional)
            'map_point_mapping' => 'nullable|array',
            'map_point_mapping.enabled' => 'boolean',
            'map_point_mapping.title_field_id' => ['nullable', 'string', new FormFieldExistsInRequest],
            'map_point_mapping.description_field_id' => ['nullable', 'string', new FormFieldExistsInRequest],
            'map_point_mapping.coordinate_field_id' => ['nullable', 'string', new FormFieldExistsInRequest],

            'group_id' => 'required|exists:groups,uuid',
        ];

        if ($this->isMapPointFieldsDefinition()) {
            $rules['fields.*.type'] = ['required', Rule::enum(FieldType::class)->only(FieldType::typesForMapPointFields)];
        }

        if ($this->isMapPointMappingEnabled()) {
            $rules['map_point_mapping.category_id'] = ['nullable', 'uuid', 'exists:map_point_categories,uuid'];
            $rules['map_point_mapping.subcategory_field_id'] = ['nullable', 'string', new FormFieldExistsInRequest];
            $rules['map_point_mapping.subcategory_options'] = ['array'];
            $rules['map_point_mapping.subcategory_options.*.option_value'] = ['required', 'string'];
            $rules['map_point_mapping.subcategory_options.*.category_id'] = ['required', 'uuid', 'exists:map_point_categories,uuid'];
            $rules['map_point_mapping.characteristics_field_id'] = ['nullable', 'string', new FormFieldExistsInRequest];
            $rules['map_point_mapping.characteristic_options'] = ['array'];
            $rules['map_point_mapping.characteristic_options.*.option_value'] = ['required', 'string'];
            $rules['map_point_mapping.characteristic_options.*.characteristic_id'] = ['required', 'uuid', 'exists:map_point_characteristics,uuid'];
            $rules['map_point_mapping.field_mappings'] = ['array'];
            $rules['map_point_mapping.field_mappings.*.target_field_id'] = ['required', 'uuid', 'exists:form_fields,uuid'];
            $rules['map_point_mapping.field_mappings.*.source_field_id'] = ['required', 'string', new FormFieldExistsInRequest];
        }

        if ($this->has('advice_mapping') && ! is_null($this->input('advice_mapping')) && $this->input('advice_mapping.enabled') === true) {
            $rules['advice_mapping.first_name_field_id'] = ['nullable', 'string', new FormFieldExistsInRequest];
            $rules['advice_mapping.last_name_field_id'] = ['nullable', 'string', new FormFieldExistsInRequest];
            $rules['advice_mapping.address_field_id'] = [
                'nullable',
                'string',
                new FormFieldExistsInRequest,
                new MappedFormFieldMustBeRequired(
                    readableName: 'Adresse Feld',
                    reason: 'da jede Beratung eine Adresse braucht. Weitere Adressfelder im Formular kannst du optional lassen.',
                ),
            ];
            $rules['advice_mapping.email_field_id'] = ['nullable', 'string', new FormFieldExistsInRequest];
            $rules['advice_mapping.phone_field_id'] = ['nullable', 'string', new FormFieldExistsInRequest];
            $rules['advice_mapping.advice_type_field_id'] = ['nullable', 'string', 'required_without:advice_mapping.advice_type_direct', new FormFieldExistsInRequest];
            $rules['advice_mapping.advice_type_direct'] = 'nullable|string|required_without:advice_mapping.advice_type_field_id';
            $rules['advice_mapping.advice_type_home_option_value'] = 'nullable|string|required_with:advice_mapping.advice_type_field_id';
            $rules['advice_mapping.advice_type_virtual_option_value'] = 'nullable|string|required_with:advice_mapping.advice_type_field_id';
            $rules['advice_mapping.default_group_id'] = 'nullable|string';
            $rules['advice_mapping.condition_field_id'] = ['nullable', 'string', new FormFieldExistsInRequest];
        }

        return $rules;
    }

    /**
     * The fields of a map point category are created through the category and must stay such fields,
     * otherwise they could be turned into a public form or a checklist and back.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $existing = $this->route('form_definition');
                $existingType = $existing instanceof FormDefinition ? $existing->type : null;
                $requestedType = FormType::tryFrom((int) $this->input('type', FormType::Form->value));

                if ($existingType === null && $requestedType === FormType::MapPointFields) {
                    $validator->errors()->add('type', 'Felder einer Kartenpunkt-Kategorie legst du über die Kategorie an.');
                }

                if ($existingType !== null && $existingType !== $requestedType && in_array(FormType::MapPointFields, [$existingType, $requestedType], true)) {
                    $validator->errors()->add('type', 'Der Typ von Kategorie-Feldern kann nicht geändert werden.');
                }
            },
            function (Validator $validator): void {
                if ($this->boolean('requires_email_confirmation')) {
                    $this->validateEmailConfirmationField($validator);
                }
            },
            function (Validator $validator): void {
                if ($this->input('advice_mapping.enabled') === true && $this->input('advice_mapping.condition_field_id') !== null) {
                    $this->validateAdviceConditionField($validator);
                }
            },
            function (Validator $validator): void {
                if ($this->isMapPointMappingEnabled() && ! $validator->errors()->hasAny(['group_id', 'map_point_mapping.*'])) {
                    $this->validateMapPointCategoryMapping($validator);
                }
            },
        ];
    }

    /**
     * The confirmation mail needs a single, unambiguous address that is always filled in.
     */
    private function validateEmailConfirmationField(Validator $validator): void
    {
        $emailFields = $this->collect('fields')->where('type', FieldType::EMAIL->value);

        if ($emailFields->count() !== 1) {
            $validator->errors()->add('requires_email_confirmation', 'Für die E-Mail-Bestätigung braucht das Formular genau ein E-Mail-Feld.');
        } elseif (! filter_var($emailFields->first()['required'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $validator->errors()->add('requires_email_confirmation', 'Für die E-Mail-Bestätigung muss das E-Mail-Feld ein Pflichtfeld sein.');
        }
    }

    private function validateAdviceConditionField(Validator $validator): void
    {
        $field = $this->collect('fields')->firstWhere('id', $this->input('advice_mapping.condition_field_id'));

        if ($field === null) {
            return;
        }

        if (($field['type'] ?? null) !== FieldType::CHECKBOX->value || count($field['options'] ?? []) !== 1) {
            $validator->errors()->add('advice_mapping.condition_field_id', 'Als Bedingung für die Beratung eignet sich nur eine Checkbox mit genau einer Option.');
        }
    }

    private function isMapPointMappingEnabled(): bool
    {
        return $this->boolean('map_point_mapping.enabled');
    }

    /**
     * Checks the category of created points, which options pick characteristics and which form fields fill the fields. Sub categories picked by an option
     * must lie below the category and use its fields, because the field mappings refer to the fields of the category.
     */
    private function validateMapPointCategoryMapping(Validator $validator): void
    {
        $categoryUuid = $this->input('map_point_mapping.category_id');

        if ($categoryUuid === null) {
            return;
        }

        $category = MapPointCategory::where('uuid', $categoryUuid)->firstOrFail();
        $group = Group::where('uuid', $this->input('group_id'))->firstOrFail();

        if (! $category->isUsableInGroup($group)) {
            $validator->errors()->add('map_point_mapping.category_id', 'Diese Kategorie ist für die Initiative des Formulars nicht verfügbar.');

            return;
        }

        $submittedFields = $this->collect('fields')->keyBy('id');
        $tree = MapPointCategory::tree();

        $subcategoryField = $submittedFields->get($this->input('map_point_mapping.subcategory_field_id'));

        if ($subcategoryField !== null && ! in_array($subcategoryField['type'] ?? null, [FieldType::SELECT->value, FieldType::RADIO->value], true)) {
            $validator->errors()->add('map_point_mapping.subcategory_field_id', 'Die Unterkategorie kann nur ein Auswahlfeld oder Radio-Buttons bestimmen.');
        }

        $subcategoryOptions = $this->collect('map_point_mapping.subcategory_options');
        $subcategories = MapPointCategory::whereIn('uuid', $subcategoryOptions->pluck('category_id'))->get()->keyBy('uuid');

        foreach ($subcategoryOptions as $index => $option) {
            $subcategory = $subcategories[$option['category_id']];
            $isBelowWithSameFields = in_array($subcategory->id, $tree->descendantIds($category->id), true)
                && $tree->fieldsCategoryId($subcategory->id) === $tree->fieldsCategoryId($category->id);

            if (! $isBelowWithSameFields) {
                $validator->errors()->add(
                    "map_point_mapping.subcategory_options.{$index}.category_id",
                    "Die Unterkategorie {$subcategory->name} muss unter der Kategorie {$category->name} liegen und deren Felder übernehmen.",
                );
            }
        }

        $characteristicsField = $submittedFields->get($this->input('map_point_mapping.characteristics_field_id'));

        if ($characteristicsField !== null && ($characteristicsField['type'] ?? null) !== FieldType::CHECKBOX->value) {
            $validator->errors()->add('map_point_mapping.characteristics_field_id', 'Die Maßnahmen können nur Checkboxen bestimmen.');
        }

        $selectableCharacteristics = MapPointCharacteristic::selectableFor($category->id, $tree)->get();

        foreach ($this->collect('map_point_mapping.characteristic_options') as $index => $option) {
            if (! $selectableCharacteristics->contains('uuid', $option['characteristic_id'])) {
                $validator->errors()->add(
                    "map_point_mapping.characteristic_options.{$index}.characteristic_id",
                    "Diese Maßnahme ist für die Kategorie {$category->name} nicht verfügbar.",
                );
            }
        }

        // Fields of characteristics can be filled as well. They are only stored when the characteristic is chosen.
        $categoryFields = app(MapPointFieldService::class)
            ->effectiveFields($category->id, $selectableCharacteristics->modelKeys(), $tree)
            ->keyBy('uuid');

        foreach ($this->input('map_point_mapping.field_mappings', []) as $index => $fieldMapping) {
            $targetField = $categoryFields->get($fieldMapping['target_field_id']);
            $sourceType = FieldType::tryFrom($submittedFields->get($fieldMapping['source_field_id'])['type'] ?? '');

            if ($targetField === null) {
                $validator->errors()->add("map_point_mapping.field_mappings.{$index}.target_field_id", 'Dieses Feld gehört weder zur Kategorie der Kartenpunkte noch zu ihren Maßnahmen.');
            } elseif ($sourceType === null || ! in_array($sourceType, $targetField->type->mapPointFieldSourceTypes(), true)) {
                $validator->errors()->add(
                    "map_point_mapping.field_mappings.{$index}.source_field_id",
                    "Das Formularfeld passt nicht zum Typ des Kategoriefelds {$targetField->label}.",
                );
            }
        }
    }

    private function isMapPointFieldsDefinition(): bool
    {
        $existing = $this->route('form_definition');

        return $existing instanceof FormDefinition && $existing->type === FormType::MapPointFields;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'name' => 'Formularname',
            'description' => 'Beschreibung',
            'is_active' => 'Aktiv',
            'success_message' => 'Erfolgsmeldung',
            'show_next_form_button' => 'Button anzeigen',
            'next_form_button_text' => 'Button-Text',
            'allowed_embed_domains' => 'Erlaubte Domains für Einbettung',
            'requires_email_confirmation' => 'E-Mail-Adresse bestätigen lassen',
            'fields' => 'Formularfelder',
            'fields.*.type' => 'Feldtyp',
            'fields.*.label' => 'Feldbezeichnung',
            'fields.*.placeholder' => 'Platzhaltertext',
            'fields.*.help_text' => 'Hilfetext',
            'fields.*.required' => 'Pflichtfeld',
            'fields.*.default_value' => 'Standardwert',
            'fields.*.options' => 'Optionen',
            'fields.*.options.*.label' => 'Optionsbezeichnung',
            'fields.*.options.*.value' => 'Optionswert',
            'advice_mapping.first_name_field_id' => 'Vorname Feld',
            'advice_mapping.last_name_field_id' => 'Nachname Feld',
            'advice_mapping.address_field_id' => 'Adresse Feld',
            'advice_mapping.email_field_id' => 'E-Mail Feld',
            'advice_mapping.phone_field_id' => 'Telefon Feld',
            'advice_mapping.advice_type_field_id' => 'Beratungstyp Feld',
            'advice_mapping.condition_field_id' => 'Bedingung für die Beratung',
            'map_point_mapping.title_field_id' => 'Titel Feld',
            'map_point_mapping.description_field_id' => 'Beschreibung Feld',
            'map_point_mapping.coordinate_field_id' => 'Koordinaten Feld',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'fields.*.options.required_if' => 'Für Auswahlfelder müssen Optionen angegeben werden.',
        ];
    }
}
