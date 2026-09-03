<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class BuildConsultingAreaFromPostalCodesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageArea', $this->route('group'));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'postal_codes' => ['required', 'array', 'min:1'],
            'postal_codes.*' => ['required', 'string', 'regex:/^\d{5}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'postal_codes.required' => 'Bitte gib mindestens eine Postleitzahl an.',
            'postal_codes.min' => 'Bitte gib mindestens eine Postleitzahl an.',
            'postal_codes.*.regex' => 'Postleitzahlen müssen aus fünf Ziffern bestehen.',
        ];
    }
}
