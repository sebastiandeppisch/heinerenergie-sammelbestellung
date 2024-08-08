<?php

namespace App\Rules;

use App\Models\Setting;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class OrderPassword implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (Auth::user() !== null) {
            return true;
        }

        return $value === Setting::get('orderFormPassword');
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Das Bestellungs-Passwort ist ungültig';
    }
}
