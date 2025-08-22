<?php

namespace App\Http\Requests\Group;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('group'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'accepts_transfers' => 'boolean',
            'logo' => 'nullable|image|max:1024', // max 1MB
            'remove_logo' => 'boolean',
        ];
    }
}
