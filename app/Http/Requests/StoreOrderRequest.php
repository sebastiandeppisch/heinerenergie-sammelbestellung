<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'email' => 'required|email|confirmed',
            'email_confirmation' => 'required|email',
            'phone' => 'required|string',
            'street' => 'required|string',
            'streetNumber' => 'required|string',
            'zip' => 'required|digits:5',
            'city' => 'required|string',
        ];
    }
}
