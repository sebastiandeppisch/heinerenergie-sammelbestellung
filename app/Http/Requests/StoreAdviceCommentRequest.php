<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdviceCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('advice'));
    }

    public function rules(): array
    {
        return [
            'comment' => 'required|string|max:65535',
        ];
    }
}
