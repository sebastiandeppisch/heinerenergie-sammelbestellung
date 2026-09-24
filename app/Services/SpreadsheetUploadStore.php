<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SpreadsheetFormat;
use App\Models\Group;
use App\ValueObjects\SpreadsheetUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Keeps uploaded spreadsheets on the local disk between upload and import, so the user can choose
 * the column mapping before anything is written. An upload is referenced by a token in the session
 * and can only be used by the same user within the group it was uploaded to.
 */
class SpreadsheetUploadStore
{
    public const string DISK = 'local';

    private const string DIRECTORY = 'spreadsheet-uploads';

    private const string SESSION_KEY = 'spreadsheet_uploads';

    public function store(UploadedFile $file, Group $group): SpreadsheetUpload
    {
        $format = SpreadsheetFormat::fromFilename($file->getClientOriginalName());

        if ($format === null) {
            throw ValidationException::withMessages(['file' => 'Dieses Dateiformat wird nicht unterstützt.']);
        }

        $token = (string) Str::uuid();
        $path = $file->storeAs(self::DIRECTORY, "{$token}.{$format->value}", self::DISK);

        if ($path === false) {
            throw ValidationException::withMessages(['file' => 'Die Datei konnte nicht gespeichert werden.']);
        }

        session()->put(self::SESSION_KEY.'.'.$token, [
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'group_id' => $group->id,
        ]);

        return new SpreadsheetUpload($token, $path, $file->getClientOriginalName());
    }

    public function find(string $token, Group $group): SpreadsheetUpload
    {
        $upload = session()->get(self::SESSION_KEY.'.'.$token);

        if (
            ! is_array($upload)
            || ($upload['group_id'] ?? null) !== $group->id
            || ! is_string($upload['path'] ?? null)
            || ! is_string($upload['filename'] ?? null)
            || ! Storage::disk(self::DISK)->exists($upload['path'])
        ) {
            throw new NotFoundHttpException('Die hochgeladene Datei wurde nicht gefunden.');
        }

        return new SpreadsheetUpload($token, $upload['path'], $upload['filename']);
    }

    public function forget(string $token): void
    {
        $upload = session()->pull(self::SESSION_KEY.'.'.$token);

        if (is_array($upload) && is_string($upload['path'] ?? null)) {
            Storage::disk(self::DISK)->delete($upload['path']);
        }
    }
}
