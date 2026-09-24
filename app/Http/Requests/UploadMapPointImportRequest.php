<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\MapPoint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class UploadMapPointImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('import', MapPoint::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'extensions:csv,txt,xls,xlsx,ods', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'Datei',
        ];
    }

    public function uploadedFile(): UploadedFile
    {
        $file = $this->file('file');

        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages(['file' => 'Bitte wähle genau eine Datei aus.']);
        }

        return $file;
    }
}
