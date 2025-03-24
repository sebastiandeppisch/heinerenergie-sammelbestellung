<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupConsultingAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageArea', $this->route('group'));
    }

    public function rules(): array
    {
        return [
            'polygon' => ['required', 'array'],
            'polygon.*.lng' => ['required', 'numeric', 'between:-180,180'],
            'polygon.*.lat' => ['required',  'numeric', 'between:-90,90'],
        ];
    }

    public function messages(): array
    {
        return [
            'polygon.required' => 'Das Polygon ist erforderlich.',
            'polygon.array' => 'Das Polygon muss ein Array sein.',
            'polygon.*.lng.numeric' => 'Koordinaten müssen numerisch sein.',
            'polygon.*.lat.numeric' => 'Koordinaten müssen numerisch sein.',
            'polygon.*.lng.between' => 'Geographische Längenangaben müssen zwischen -180 und 180 liegen.',
            'polygon.*.lat.between' => 'Geographische Breitenangaben müssen zwischen -90 und 90 liegen.',
        ];
    }
}
