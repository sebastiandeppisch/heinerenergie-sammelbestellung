<?php

namespace App\Http\Requests;

use App\Models\Group;
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
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'phone' => 'required|string|max:100',
            'street' => 'required|string|max:100',
            'street_number' => 'required|string|max:100',
            'zip' => 'required|numeric|digits:5',
            'city' => 'required|string|max:100',
            'advisor_id' => 'nullable|uuid|exists:users,id',
            'commentary' => 'nullable|string|max:65535',
            'help_type_place' => 'nullable|boolean',
            'help_type_technical' => 'nullable|boolean',
            'help_type_bureaucracy' => 'nullable|boolean',
            'helpType_other' => 'nullable|boolean',
            'house_type' => 'nullable|integer|between:0,2',
            'landlord_exists' => 'nullable|boolean',
            'place_notes' => 'nullable|string|max:65535',
            'type' => 'integer|between:0,2',
            'group_id' => 'uuid|exists:groups,uuid',
        ];
    }

    public function getData(): array
    {
        $validated = $this->validated();
        $validated['group_id'] = Group::where('uuid', $validated['group_id'])->first()->id;

        return $validated;
    }
}
