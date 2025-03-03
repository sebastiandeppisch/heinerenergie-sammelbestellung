<?php

namespace App\Http\Requests;

use App\Enums\AdviceStatusResult;
use App\Models\AdviceStatusGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreGroupAdviceStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $group = $this->route('group');

        return $this->user()->can('create', [AdviceStatusGroup::class, $group]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|string',
            'result' => ['required', new Enum(AdviceStatusResult::class)],
            'visible' => 'boolean',
        ];
    }
}
