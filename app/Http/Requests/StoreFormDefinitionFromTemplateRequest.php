<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormDefinitionFromTemplateRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'template_type' => 'required|string|in:advice,map_point',
            'group_id' => 'required|string|exists:groups,uuid',
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
            'template_type' => 'Template-Typ',
            'group_id' => 'Initiative',
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
            'template_type.in' => 'Der gewählte Template-Typ ist ungültig.',
            'group_id.exists' => 'Die gewählte Initiative existiert nicht.',
        ];
    }
}

