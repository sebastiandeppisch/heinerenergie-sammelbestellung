<?php

namespace App\Http\Requests;

use App\Models\MapPointCategory;
use App\Rules\GeographicCoordinate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpsertMapPointRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required',
            'description' => 'nullable',
            'coordinate' => new GeographicCoordinate,
            'published' => 'boolean',
            'category_id' => 'nullable|exists:map_point_categories,uuid',
        ];
    }

    public function getData(): array
    {
        $validated = $this->validated();

        $validated['category_id'] = MapPointCategory::where('uuid', $validated['category_id'])->first()?->id;

        return $validated;
    }
}
