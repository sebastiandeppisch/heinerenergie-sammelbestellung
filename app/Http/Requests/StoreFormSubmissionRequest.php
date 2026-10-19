<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\ConsentChoiceData;
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
        return [
            ...$this->formDefinition()->getValidationRules(),
            '_consent_show' => ['nullable', 'boolean'],
            '_consent_details' => ['nullable', 'boolean'],
        ];
    }

    /**
     * The choice in the consent block forms with a map point target show. The details need the point itself.
     */
    public function consentChoice(): ConsentChoiceData
    {
        $show = $this->boolean('_consent_show');

        return new ConsentChoiceData(show: $show, details: $show && $this->boolean('_consent_details'));
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
