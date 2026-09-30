<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SelectsMapPoints;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateMapPointsCategoryRequest extends FormRequest
{
    use SelectsMapPoints;

    public function authorize(): bool
    {
        return $this->userMayForAllMapPoints('update');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->selectedMapPointRules(),
            'category_id' => ['nullable', 'bail', 'uuid', 'exists:map_point_categories,uuid'],
        ];
    }

    /**
     * Like for a single point, the category must be usable in the initiative of every point.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $category = $this->category();

                if ($validator->errors()->isNotEmpty() || $category === null) {
                    return;
                }

                if ($this->mapPoints()->contains(fn (MapPoint $mapPoint): bool => ! $category->isUsableInGroup($mapPoint->group))) {
                    $validator->errors()->add('category_id', 'Diese Kategorie ist nicht für die Initiativen aller ausgewählten Punkte verfügbar.');
                }
            },
        ];
    }

    public function category(): ?MapPointCategory
    {
        $uuid = $this->input('category_id');

        return is_string($uuid) ? MapPointCategory::where('uuid', $uuid)->first() : null;
    }
}
