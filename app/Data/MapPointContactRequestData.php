<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\MapPointContactRequest;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MapPointContactRequestData extends Data
{
    public function __construct(
        public string $id,
        public Carbon $created_at,
        public string $sender_name,
        public string $sender_email,
        public string $message,
        #[LiteralTypeScriptType("'forwarded' | 'confirmed' | 'expired' | 'pending'")]
        public string $status,
    ) {}

    public static function fromModel(MapPointContactRequest $model): self
    {
        return new self(
            id: $model->uuid,
            created_at: $model->created_at,
            sender_name: $model->sender_name,
            sender_email: $model->sender_email,
            message: $model->message,
            status: match (true) {
                $model->forwarded_at !== null => 'forwarded',
                $model->confirmed_at !== null => 'confirmed',
                $model->isConfirmationExpired() => 'expired',
                default => 'pending',
            },
        );
    }
}
