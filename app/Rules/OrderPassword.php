<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

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
        return $value === env('ORDER_PASSWORD', 'You must set ORDER_PASSWORD in .env');
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
