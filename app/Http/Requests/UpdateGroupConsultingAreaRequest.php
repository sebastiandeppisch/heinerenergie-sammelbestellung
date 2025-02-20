<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupConsultingAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Assuming the user needs to be able to edit the group
        return $this->user()->can('update', $this->route('group'));
    }

    public function rules(): array
    {
        return [
            'polygon' => ['required', 'array'],
            'polygon.*' => ['required', 'array', 'size:2'],
            'polygon.*.*' => ['required', 'numeric', 'between:0,180'], // Covers both lat/long ranges
        ];
    }

    public function messages(): array
    {
        return [
            'polygon.required' => 'Das Polygon ist erforderlich.',
            'polygon.array' => 'Das Polygon muss ein Array sein.',
            'polygon.*.array' => 'Jeder Punkt muss ein Array mit Koordinaten sein.',
            'polygon.*.size' => 'Jeder Punkt muss genau zwei Koordinaten enthalten.',
            'polygon.*.*.required' => 'Alle Koordinaten müssen angegeben werden.',
            'polygon.*.*.numeric' => 'Koordinaten müssen numerisch sein.',
            'polygon.*.*.between' => 'Koordinaten müssen zwischen 0 und 180 liegen.',
        ];
    }
} 