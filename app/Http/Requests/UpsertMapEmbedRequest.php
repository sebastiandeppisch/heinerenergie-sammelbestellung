<?php

namespace App\Http\Requests;

use App\Models\MapEmbed;
use App\Rules\GeographicCoordinate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpsertMapEmbedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $mapEmbed = $this->route('mapEmbed');

        return $mapEmbed
            ? $this->user()->can('update', $mapEmbed)
            : $this->user()->can('create', MapEmbed::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:map_point_categories,uuid',
            'coordinate' => new GeographicCoordinate,
            'zoom' => 'required|integer|min:3|max:18',
            'show_table' => 'boolean',
        ];
    }

    public function getData(): array
    {
        return $this->safe()->only(['name', 'coordinate', 'zoom', 'show_table']);
    }
}
