<?php

namespace App\Http\Requests;

use App\Enums\FormType;
use App\Models\Advice;
use App\Models\FormDefinition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChecklistEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('advice'));
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'form_definition_id' => [
                'required',
                'string',
                Rule::exists('form_definitions', 'uuid')->where(
                    fn ($query) => $query
                        ->where('type', FormType::Checklist->value)
                        ->where('group_id', $this->advice()->group_id)
                ),
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('form_definition_id')) {
                    return;
                }

                $alreadyAdded = $this->advice()
                    ->checklistEntries()
                    ->whereHas('formDefinition', fn ($query) => $query->where('uuid', $this->input('form_definition_id')))
                    ->exists();

                if ($alreadyAdded) {
                    $validator->errors()->add('form_definition_id', 'Diese Checkliste wurde dieser Beratung bereits hinzugefügt.');
                }
            },
        ];
    }

    public function checklist(): FormDefinition
    {
        return FormDefinition::where('uuid', $this->validated('form_definition_id'))->firstOrFail();
    }

    private function advice(): Advice
    {
        return $this->route('advice');
    }
}
