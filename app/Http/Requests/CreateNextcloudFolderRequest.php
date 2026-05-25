<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateNextcloudFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('advice'));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'parent_path' => 'required|string|max:1000',
        ];
    }
}
