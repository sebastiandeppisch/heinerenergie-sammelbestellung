<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportNextcloudUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('group'));
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'send_email' => ['required', 'boolean'],
        ];
    }

    public function firstName(): string
    {
        return $this->validated('first_name');
    }

    public function lastName(): string
    {
        return $this->validated('last_name');
    }

    public function sendEmail(): bool
    {
        return $this->boolean('send_email');
    }
}
