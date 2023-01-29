<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            "street" => "nullable|string",
            "streetNumber" => "nullable|string",
            "zip" => "nullable|integer",
            "city" => "nullable|string",
        ];
    }
}
