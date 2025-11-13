<?php

namespace App\Http\Requests;

use App\Enums\FieldType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class UpsertFormDefinitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',

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
            'advice_mapping.first_name_field_id' => 'nullable|string',
            'advice_mapping.last_name_field_id' => 'nullable|string',
            'advice_mapping.address_field_id' => 'nullable|string',
            'advice_mapping.email_field_id' => 'nullable|string',
            'advice_mapping.phone_field_id' => 'nullable|string',
            'advice_mapping.advice_type_field_id' => 'nullable|string',
            'advice_mapping.advice_type_home_option_value' => 'nullable|string',
            'advice_mapping.advice_type_virtual_option_value' => 'nullable|string',
            'advice_mapping.default_group_id' => 'nullable|string',

            // Map point mapping (optional)
            'map_point_mapping' => 'nullable|array',
            'map_point_mapping.enabled' => 'boolean',
            'map_point_mapping.title_field_id' => 'nullable|string',
            'map_point_mapping.description_field_id' => 'nullable|string',
            'map_point_mapping.coordinate_field_id' => 'nullable|string',
        ];
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
