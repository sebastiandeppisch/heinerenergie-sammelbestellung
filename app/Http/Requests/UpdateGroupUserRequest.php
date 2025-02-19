<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageUsers', $this->route('group'));
    }

    public function rules(): array
    {
        return [
            'is_admin' => 'required|boolean',
        ];
    }
}
