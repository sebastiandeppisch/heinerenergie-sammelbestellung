<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Rules\GeographicCoordinate;
use App\Services\MapPointVisibilityService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpsertMapPointRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $mapPoint = $this->route('mappoint');

        return $mapPoint instanceof MapPoint
            ? $this->user()->can('update', $mapPoint)
            : $this->user()->can('create', MapPoint::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required'],
            'description' => ['nullable'],
            'coordinate' => new GeographicCoordinate,
            'published' => ['boolean'],
            'group_id' => ['required', 'bail', 'uuid', 'exists:groups,uuid'],
            'category_id' => ['nullable', 'bail', 'uuid', 'exists:map_point_categories,uuid'],
            'location' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'contact_enabled' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'group_id' => 'Initiative',
            'category_id' => 'Kategorie',
            'contact_email' => 'Kontaktadresse',
        ];
    }

    /**
     * The point may only be assigned to a group the user administers, and its category
     * must be usable in that group.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['group_id', 'category_id'])) {
                    return;
                }

                $group = Group::where('uuid', $this->input('group_id'))->firstOrFail();

                if (! app(MapPointVisibilityService::class)->isSelectableGroup($this->user(), $group)) {
                    $validator->errors()->add('group_id', 'Du darfst dieser Initiative keine Kartenpunkte zuordnen.');

                    return;
                }

                $categoryUuid = $this->input('category_id');

                if ($categoryUuid === null) {
                    return;
                }

                $category = MapPointCategory::where('uuid', $categoryUuid)->firstOrFail();

                if (! $category->isUsableInGroup($group)) {
                    $validator->errors()->add('category_id', 'Diese Kategorie ist für die gewählte Initiative nicht verfügbar.');
                }
            },
        ];
    }

    /**
     * Group and category are submitted as uuids and translated into foreign keys here.
     * An address entered by an admin counts as confirmed.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $data = [
            ...$this->safe()->except(['group_id', 'category_id']),
            'group_id' => Group::where('uuid', $this->validated('group_id'))->value('id'),
            'category_id' => MapPointCategory::where('uuid', $this->validated('category_id'))->value('id'),
        ];

        if ($this->has('contact_email')) {
            $contactEmail = $this->validated('contact_email');
            $mapPoint = $this->route('mappoint');
            $previousEmail = $mapPoint instanceof MapPoint ? $mapPoint->contact_email : null;

            $data['contact_email'] = $contactEmail;

            if ($contactEmail === null) {
                $data['contact_email_verified_at'] = null;
            } elseif ($contactEmail !== $previousEmail) {
                $data['contact_email_verified_at'] = now();
            }
        }

        return $data;
    }
}
