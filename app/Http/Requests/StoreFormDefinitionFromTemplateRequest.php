<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Context\GroupContextContract;
use App\Models\Group;
use App\Models\MapPointCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Override;

class StoreFormDefinitionFromTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    /**
     * Forms belong to an initiative, so only its admins may create one there.
     */
    public function authorize(): bool
    {
        $group = Group::where('uuid', $this->input('group_id'))->first();

        if ($group === null) {
            return true;
        }

        $groupContext = app(GroupContextContract::class);

        return $groupContext->isActingAsSystemAdmin($this->user()) || $groupContext->isActingAsTransitiveAdmin($this->user(), $group);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'template_type' => ['required', 'string', 'in:advice,map_point'],
            'group_id' => ['required', 'string', 'exists:groups,uuid'],
            'map_point_category_id' => ['nullable', 'bail', 'uuid', 'exists:map_point_categories,uuid'],
        ];
    }

    /**
     * The category must be usable in the initiative of the form.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $category = $this->mapPointCategory();

                if ($validator->errors()->isNotEmpty() || $category === null) {
                    return;
                }

                if (! $category->isUsableInGroup(Group::where('uuid', $this->input('group_id'))->firstOrFail())) {
                    $validator->errors()->add('map_point_category_id', 'Diese Kategorie ist für die gewählte Initiative nicht verfügbar.');
                }
            },
        ];
    }

    public function mapPointCategory(): ?MapPointCategory
    {
        $uuid = $this->input('map_point_category_id');

        return is_string($uuid) ? MapPointCategory::where('uuid', $uuid)->first() : null;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'template_type' => 'Template-Typ',
            'group_id' => 'Initiative',
            'map_point_category_id' => 'Kategorie',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'template_type.in' => 'Der gewählte Template-Typ ist ungültig.',
            'group_id.exists' => 'Die gewählte Initiative existiert nicht.',
        ];
    }
}
