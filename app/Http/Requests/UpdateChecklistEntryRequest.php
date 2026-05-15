<?php

namespace App\Http\Requests;

use App\Models\Advice;
use App\Models\ChecklistEntry;
use Illuminate\Foundation\Http\FormRequest;

class UpdateChecklistEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->advice())
            && $this->checklistEntry()->advice_id === $this->advice()->id;
    }

    public function rules(): array
    {
        return [
            'data' => 'required|array',
            'data.*' => 'nullable',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fieldValues(): array
    {
        return $this->validated('data');
    }

    private function advice(): Advice
    {
        return $this->route('advice');
    }

    private function checklistEntry(): ChecklistEntry
    {
        return $this->route('checklistEntry');
    }
}
