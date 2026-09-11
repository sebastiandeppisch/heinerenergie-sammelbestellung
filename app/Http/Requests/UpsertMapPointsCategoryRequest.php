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
     * detach it from points of groups that can no longer use it.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
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
        ];
    }

    /**
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $data = $this->safe()->only(['name']);

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
