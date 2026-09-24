<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\SpreadsheetFormat;
use App\Models\MapPoint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        ];
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
