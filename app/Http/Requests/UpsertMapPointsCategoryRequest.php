<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Group;
use App\Models\MapPointCategory;
use App\Services\MapPointVisibilityService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpsertMapPointsCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', MapPointCategory::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The owning group is only set on creation. Moving a category to another group would
     * detach it from points of groups that can no longer use it. The parent can be changed.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'parent_id' => ['nullable', 'bail', 'uuid', 'exists:map_point_categories,uuid'],
            'group_id' => $this->isCreating()
                ? ['required', 'bail', 'uuid', 'exists:groups,uuid']
                : ['exclude'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'group_id' => 'Initiative',
            'parent_id' => 'Oberkategorie',
        ];
    }

    /**
     * The parent must be usable in the category's group, so it belongs to the same group or an ancestor.
     * This keeps every branch visible to each group that sees its leaves.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->isCreating() || $validator->errors()->has('group_id')) {
                    return;
                }

                $group = Group::where('uuid', $this->input('group_id'))->firstOrFail();

                if (! app(MapPointVisibilityService::class)->isSelectableGroup($this->user(), $group)) {
                    $validator->errors()->add('group_id', 'Du darfst für diese Initiative keine Kategorien anlegen.');
                }
            },
            function (Validator $validator): void {
                if ($this->input('parent_id') === null || $validator->errors()->hasAny(['group_id', 'parent_id'])) {
                    return;
                }

                $parent = MapPointCategory::where('uuid', $this->input('parent_id'))->firstOrFail();
                $category = $this->route('mappoint_category');

                if ($category instanceof MapPointCategory && in_array($parent->id, [$category->id, ...MapPointCategory::tree()->descendantIds($category->id)], true)) {
                    $validator->errors()->add('parent_id', 'Eine Kategorie kann nicht sich selbst oder einer ihrer Unterkategorien untergeordnet werden.');

                    return;
                }

                $group = $category instanceof MapPointCategory
                    ? $category->group
                    : Group::where('uuid', $this->input('group_id'))->firstOrFail();

                if (! $parent->isUsableInGroup($group)) {
                    $validator->errors()->add('parent_id', 'Die Oberkategorie muss zur selben oder einer übergeordneten Initiative gehören.');
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $data = $this->safe()->only(['name']);

        if ($this->has('parent_id')) {
            $parentUuid = $this->validated('parent_id');
            $data['parent_id'] = $parentUuid === null ? null : MapPointCategory::where('uuid', $parentUuid)->value('id');
        }

        if ($this->isCreating()) {
            $data['group_id'] = Group::where('uuid', $this->validated('group_id'))->value('id');
        }

        return $data;
    }

    private function isCreating(): bool
    {
        return ! $this->route('mappoint_category') instanceof MapPointCategory;
    }
}
