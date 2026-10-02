<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SelectsMapPoints;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateMapPointsCharacteristicRequest extends FormRequest
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
            'characteristic_id' => ['required', 'bail', 'uuid', 'exists:map_point_characteristics,uuid'],
            'action' => ['required', 'in:add,remove'],
        ];
    }

    /**
     * Like for a single point, an added characteristic must be offered by the category of every point. Removing is always possible.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || ! $this->isAdding()) {
                    return;
                }

                $characteristic = $this->characteristic();
                $tree = MapPointCategory::tree();

                if ($this->mapPoints()->contains(fn (MapPoint $mapPoint): bool => ! $characteristic->isSelectableFor($mapPoint->category_id, $tree))) {
                    $validator->errors()->add('characteristic_id', 'Diese Maßnahme ist nicht für die Kategorien aller ausgewählten Punkte verfügbar.');
                }
            },
        ];
    }

    public function characteristic(): MapPointCharacteristic
    {
        return MapPointCharacteristic::where('uuid', $this->input('characteristic_id'))->firstOrFail();
    }

    public function isAdding(): bool
    {
        return $this->input('action') === 'add';
    }
}
