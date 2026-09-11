<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPointCategory;
use App\Rules\GeographicCoordinate;
use App\Services\MapPointVisibilityService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpsertMapEmbedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $mapEmbed = $this->route('map_embed');

        return $mapEmbed instanceof MapEmbed
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
            'group_id' => ['required', 'bail', 'uuid', 'exists:groups,uuid'],
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
            'category_ids' => 'Kategorien',
        ];
    }

    /**
     * The embed may only belong to a group the user administers. Its categories must be able to
     * supply points to the embed's map, so categories of unrelated groups cannot leak into its legend.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('group_id') || $validator->errors()->has('category_ids*')) {
                    return;
                }

                $group = Group::where('uuid', $this->input('group_id'))->firstOrFail();

                if (! app(MapPointVisibilityService::class)->isSelectableGroup($this->user(), $group)) {
                    $validator->errors()->add('group_id', 'Du darfst dieser Initiative keine Einbettungen zuordnen.');

                    return;
                }

                $hasUnavailableCategory = MapPointCategory::whereIn('uuid', $this->input('category_ids'))
                    ->get()
                    ->contains(fn (MapPointCategory $category): bool => ! $category->isAvailableOnMapOfGroup($group));

                if ($hasUnavailableCategory) {
                    $validator->errors()->add('category_ids', 'Mindestens eine Kategorie ist für die gewählte Initiative nicht verfügbar.');
                }
            },
        ];
    }

    /**
     * The initiative is submitted as a uuid and translated into the foreign key here.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return [
            ...$this->safe()->only(['name', 'coordinate', 'zoom', 'show_table', 'aspect_ratio_width', 'aspect_ratio_height']),
            'group_id' => Group::where('uuid', $this->validated('group_id'))->value('id'),
        ];
    }
}
