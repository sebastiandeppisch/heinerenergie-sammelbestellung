<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMailAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'imap_host' => ['required', 'string'],
            'imap_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'smtp_host' => ['required', 'string'],
            'smtp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
