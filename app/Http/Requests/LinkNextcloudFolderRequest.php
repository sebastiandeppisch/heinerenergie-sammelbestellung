<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LinkNextcloudFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('advice'));
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'fileId' => 'required|string|max:255',
            'path' => 'required|string|max:1000',
        ];
    }
}
