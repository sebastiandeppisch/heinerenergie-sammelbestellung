<?php

namespace App\Http\Requests\Group;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;

class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->input('parent_id')) {
            $parent = Group::where('uuid', $this->input('parent_id'))->firstOrFail();

            return $this->user()->can('create', $parent);
        }

        return $this->user()->can('create', Group::class);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:groups,uuid',
        ];
    }

    public function parentId(): ?int
    {
        return $this->input('parent_id') ? Group::where('uuid', $this->input('parent_id'))->first()->id : null;
    }
}
