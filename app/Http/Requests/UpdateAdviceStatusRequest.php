<?php

namespace App\Http\Requests;

use App\Enums\AdviceStatusResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateAdviceStatusRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'string',
            'result' => new Enum(AdviceStatusResult::class),
        ];
    }
}
