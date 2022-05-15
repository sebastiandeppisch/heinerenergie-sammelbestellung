<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'firstName' => 'string',
            'lastName' => 'string',
            'email' => 'email',
            'phone' => 'string',
            'street' => 'string',
            'streetNumber' => 'string',
            'zip' => 'numeric|digits:5',
            'city' => 'string'
        ];
    }
}
