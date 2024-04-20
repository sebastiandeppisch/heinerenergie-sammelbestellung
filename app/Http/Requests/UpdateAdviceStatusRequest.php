<?php

namespace App\Http\Requests;

use App\Enums\AdviceStatusResult;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdviceStatusRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'string',
            'result' => new Enum(AdviceStatusResult::class)
        ];
    }
}
