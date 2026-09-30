<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SelectsMapPoints;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMapPointsPublishedRequest extends FormRequest
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
            'published' => ['required', 'boolean'],
        ];
    }
}
