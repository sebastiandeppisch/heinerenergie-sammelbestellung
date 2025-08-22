<?php

namespace App\Data;

use App\Models\Advice;
use App\Models\FormSubmission;
use App\Models\MapPoint;
use App\ValueObjects\Coordinate;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MapPointData extends Data
{
    public function __construct(
        public string $id,
        public Coordinate $coordinate,
        public string $title,
        public string $description,
        public bool $published,
        public string $userReadablePointableType,
        public Carbon $created_at
    ) {}

    public static function fromModel(MapPoint $model)
    {
        return new self(
            id: $model->uuid,
            coordinate: $model->coordinate,
            title: $model->title,
            description: $model->description,
            published: $model->published,
            userReadablePointableType: self::formatType($model->pointable_type),
            created_at: $model->created_at
        );
    }

    private static function formatType(?string $type)
    {
        // TODO load from model itself
        if ($type === FormSubmission::class) {
            return 'Formular';
        }
        if ($type === Advice::class) {
            return 'Beratung';
        }

        return 'Manuell';
    }
}
