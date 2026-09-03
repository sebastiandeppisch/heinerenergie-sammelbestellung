<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Group;
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
            'name' => ['nullable', 'string', 'max:255'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['bail', 'uuid', 'exists:map_point_categories,uuid'],
            'coordinate' => new GeographicCoordinate,
            'zoom' => ['required', 'integer', 'min:3', 'max:18'],
            'show_table' => ['boolean'],
            'group_id' => ['nullable', 'uuid', 'exists:groups,uuid'],
            'aspect_ratio_width' => ['required', 'integer', 'min:1', 'max:21'],
            'aspect_ratio_height' => ['required', 'integer', 'min:1', 'max:21'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'group_id' => 'Initiative',
            'category_ids' => 'Kategorien'
        ];
    }

    /**
     * The initiative is submitted as a uuid and translated into the foreign key here.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $groupUuid = $this->safe()->string('group_id')->toString();

        return [
            ...$this->safe()->only(['name', 'coordinate', 'zoom', 'show_table', 'aspect_ratio_width', 'aspect_ratio_height']),
            'group_id' => $groupUuid === '' ? null : Group::where('uuid', $groupUuid)->value('id'),
        ];
    }
}
