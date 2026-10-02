<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Characteristics are managed by those who may change their category. Admins of sub initiatives can only assign them to points.
 */
class UpsertMapPointCharacteristicRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['nullable', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'remove_icon' => ['sometimes', 'boolean'],
            // Fields only exist after creation. Checkboxes of an empty list are not sent as form data, so a missing list means no public field.
            'public_field_ids' => $this->isCreating() ? ['exclude'] : ['sometimes', 'array'],
            'public_field_ids.*' => $this->isCreating() ? ['exclude'] : ['string', 'uuid', $this->ownFieldRule()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'color' => 'Farbe',
            'icon' => 'Symbol',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'color.regex' => 'Die Farbe muss als Hex-Wert wie #2e7d32 angegeben werden.',
        ];
    }

    public function category(): MapPointCategory
    {
        $characteristic = $this->route('map_point_characteristic');

        if ($characteristic instanceof MapPointCharacteristic) {
            return $characteristic->category;
        }

        $category = $this->route('mappoint_category');

        if (! $category instanceof MapPointCategory) {
            abort(404);
        }

        return $category;
    }

    /**
     * @return array{name: string, color: string|null}
     */
    public function getData(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'color' => $this->validated('color'),
        ];
    }

    /**
     * The uuids of the characteristic's own fields to show on the public map.
     *
     * @return array<int, string>
     */
    public function publicFieldIds(): array
    {
        return $this->validated('public_field_ids') ?? [];
    }

    /**
     * Matches the fields of the characteristic's form definition. Without one, no field matches.
     */
    private function ownFieldRule(): Exists
    {
        $characteristic = $this->route('map_point_characteristic');
        $formDefinitionId = $characteristic instanceof MapPointCharacteristic ? $characteristic->form_definition_id : null;

        return Rule::exists('form_fields', 'uuid')->where('form_definition_id', $formDefinitionId);
    }

    private function isCreating(): bool
    {
        return ! $this->route('map_point_characteristic') instanceof MapPointCharacteristic;
    }
}
