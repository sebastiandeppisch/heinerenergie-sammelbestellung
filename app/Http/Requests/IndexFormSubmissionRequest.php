<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexFormSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'selectedFormDefinitions' => 'array',
            'selectedFormDefinitions.*' => 'string|exists:form_definitions,id',
            'sortOrder' => 'string|in:asc,desc',
            'groupByForm' => 'string|in:true,false',
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date|after_or_equal:dateFrom',
        ];
    }

    public function selectedFormDefinitions(): array
    {
        return $this->input('selectedFormDefinitions', []);
    }

    public function sorting(): string
    {
        return $this->input('sortOrder', 'desc');
    }

    public function groupByForm(): bool
    {
        return $this->input('groupByForm', false) === 'true';
    }

    public function dateFrom(): ?string
    {
        return $this->input('dateFrom', null);
    }

    public function dateTo(): ?string
    {
        return $this->input('dateTo', null);
    }
}
