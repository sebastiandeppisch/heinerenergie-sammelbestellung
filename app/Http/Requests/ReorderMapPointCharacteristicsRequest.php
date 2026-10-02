<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\MapPointCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderMapPointCharacteristicsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->category());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['uuid', 'distinct', Rule::exists('map_point_characteristics', 'uuid')->where('map_point_category_id', $this->category()->id)],
        ];
    }

    public function category(): MapPointCategory
    {
        $category = $this->route('mappoint_category');

        if (! $category instanceof MapPointCategory) {
            abort(404);
        }

        return $category;
    }

    /**
     * @return array<int, string>
     */
    public function ids(): array
    {
        return $this->validated('ids');
    }
}
