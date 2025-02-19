<?php

namespace App\Http\Requests;

use App\Rules\OrderPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class RequireOrderPasswordRequest extends FormRequest
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
        if (Auth::check()) {
            return [];
        }

        return [
            'password' => ['required', new OrderPassword()],
        ];
    }
}
