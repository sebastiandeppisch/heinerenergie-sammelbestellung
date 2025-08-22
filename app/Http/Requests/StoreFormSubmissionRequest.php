<?php

namespace App\Http\Requests;

use App\Models\FormDefinition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreFormSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->formDefinition()->getValidationRules();
    }

    #[Override]
    public function attributes()
    {
        return $this->formDefinition()->getValidationAttributes();
    }

    private function formDefinition(): FormDefinition
    {
        $formDefinition = $this->route('formDefinition');
        $formDefinition->loadMissing('fields', 'fields.options');

        return $formDefinition;
    }
}
