<?php

namespace App\Http\Requests;

use App\Enums\AdviceType;
use App\Models\AdviceStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateAdviceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('update', $this->route('advice'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'first_name' => 'string|max:100',
            'last_name' => 'string|max:100',
            'email' => 'email|max:100',
            'phone' => 'string|max:100',
            'street' => 'string|max:100',
            'street_number' => 'string|max:100',
            'zip' => 'numeric|digits:5',
            'city' => 'string|max:100',
            'advisor_id' => 'nullable|exists:users,id',
            'commentary' => 'nullable|string|max:65535',
            'type' => [new Enum(AdviceType::class)],
            'advice_status_id' => 'nullable|exists:advice_status,id',
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->has('advisor_id')) {
            $this->merge([
                'advisor_id' => $this->advisor()?->value('id'),
            ]);
        }

        if ($this->has('advice_status_id')) {
            $this->merge([
                'advice_status_id' => $this->adviceStatus()?->value('id'),
            ]);
        }
    }

    private function advisor(): ?User
    {
        return User::where('uuid', $this->input('advisor_id'))->first();
    }

    private function adviceStatus(): ?AdviceStatus
    {
        return AdviceStatus::where('uuid', $this->input('advice_status_id'))->first();
    }
}
