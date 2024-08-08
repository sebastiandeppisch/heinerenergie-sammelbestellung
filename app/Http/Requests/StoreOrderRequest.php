<?php

namespace App\Http\Requests;

use App\Rules\OrderPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

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
            'firstName' => 'required|string|max:100',
            'lastName' => 'required|string|max:100',
            'email' => 'required|email|confirmed|max:100',
            'email_confirmation' => 'required|email|max:100',
            'phone' => 'required|string|max:100',
            'street' => 'required|string|max:100',
            'streetNumber' => 'required|string|max:100',
            'zip' => 'required|numeric|digits:5',
            'city' => 'required|string|max:100',
            'orderItems' => 'array',
            'advisorEmail' => 'required|email|exists:users,email|max:100',
            'commentary' => 'nullable|string|max:65535',
            'password' => [new OrderPassword()],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'advisorEmail' => Str::lower($this->advisorEmail),
        ]);
    }

    public function attributes()
    {
        return [
            'firstName' => 'Vorname',
            'lastName' => 'Nachname',
            'email' => 'E-Mail Wiederholung',
            'email_confirmation' => 'E-Mail',
            'phone' => 'Telefonnummer',
            'street' => 'Straße',
            'streetNumber' => 'Hausnummer',
            'zip' => 'Postleitzahl',
            'city' => 'Stadt',
            'advisorEmail' => 'Berater*in E-Mail',
        ];
    }

    public function messages()
    {
        return [
            'advisorEmail.exists' => 'Die angegebene Berater*in E-Mail wurde nicht gefunden. Bitte verwende den Link, den Dir Dein*e Berater*in gesendet hat, dann ist die korrekte E-Mail bereits vorausgefüllt.',
        ];
    }
}
