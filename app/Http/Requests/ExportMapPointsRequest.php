<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Context\GroupContextContract;
use App\Enums\SpreadsheetFormat;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ExportMapPointsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('export', MapPoint::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mapping' => ['nullable', 'uuid'],
            'format' => ['required', Rule::enum(SpreadsheetFormat::class)],
            'category' => ['nullable', 'bail', 'uuid', 'exists:map_point_categories,uuid'],
        ];
    }

    /**
     * Like on import, only one category the group can use can be exported with its characteristics.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $category = $this->category();
                $group = app(GroupContextContract::class)->getCurrentGroup();

                if ($validator->errors()->isEmpty() && $category !== null && $group !== null && ! $category->isUsableInGroup($group)) {
                    $validator->errors()->add('category', 'Diese Kategorie ist für die Initiative nicht verfügbar.');
                }
            },
        ];
    }

    /**
     * Only the points of this category and its sub categories are exported, together with their characteristics.
     */
    public function category(): ?MapPointCategory
    {
        $uuid = $this->input('category');

        return is_string($uuid) ? MapPointCategory::where('uuid', $uuid)->first() : null;
    }

    public function spreadsheetFormat(): SpreadsheetFormat
    {
        return SpreadsheetFormat::from($this->string('format')->toString());
    }

    public function mappingUuid(): ?string
    {
        $mapping = $this->validated('mapping');

        return is_string($mapping) ? $mapping : null;
    }
}
