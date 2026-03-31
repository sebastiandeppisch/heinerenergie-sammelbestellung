<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageUsers', $this->route('group'));
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'is_admin' => ['required', 'boolean'],
        ];
    }
}
