<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdviceRequest extends FormRequest
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
            'firstName' => 'required|string|max:100',
            'lastName' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'phone' => 'required|string|max:100',
            'street' => 'required|string|max:100',
            'streetNumber' => 'required|string|max:100',
            'zip' => 'required|numeric|digits:5',
            'city' => 'required|string|max:100',
            'advisor_id' => 'nullable|integer|exists:users,id',
            'commentary' => 'nullable|string|max:65535',
            'helpType_place' => 'nullable|boolean',
            'helpType_technical' => 'nullable|boolean',
            'helpType_bureaucracy' => 'nullable|boolean',
            'helpType_other' => 'nullable|boolean',
            'houseType' => 'nullable|integer|between:0,2',
            'landlordExists' => 'nullable|boolean',
            'placeNotes' => 'nullable|string|max:65535',
            'type' => 'integer|between:0,2',
        ];
    }
}
