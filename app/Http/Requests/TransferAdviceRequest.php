<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferAdviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->advice);
    }

    public function rules(): array
    {
        return [
            'group_id' => 'required|integer|exists:groups,id',
            'reason' => 'nullable|string|max:1000',
        ];
    }
}
