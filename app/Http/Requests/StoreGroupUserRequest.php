<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreGroupUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageUsers', $this->route('group'));
    }

    public function rules(): array
    {
        $group = $this->route('group');

        return [
            'id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($group) {
                    if ($group->users()->where('users.id', $value)->exists()) {
                        $fail('Der:die Berater:in ist bereits in dieser Initiative');
                    }
                },
            ],
            'is_admin' => 'boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => User::where('uuid', $this->input('id'))->value('id'),
        ]);
    }
}
