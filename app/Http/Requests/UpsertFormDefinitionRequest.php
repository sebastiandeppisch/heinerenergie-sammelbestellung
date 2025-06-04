<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpsertFormDefinitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        //TODO
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',

            // Felder-Array-Validierung
            'fields' => 'array',
            'fields.*.type' => 'required|string|in:text,textarea,number,email,phone,select,radio,checkbox,file,date',
            'fields.*.name' => 'required|string|max:255',
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
            'fields.*.options.*.is_default' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Formularname',
            'description' => 'Beschreibung',
            'is_active' => 'Aktiv',
            'fields' => 'Formularfelder',
            'fields.*.type' => 'Feldtyp',
            'fields.*.name' => 'Feldname',
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
    public function messages(): array
    {
        return [
            'fields.*.options.required_if' => 'Für Auswahlfelder müssen Optionen angegeben werden.',
        ];
    }

}
