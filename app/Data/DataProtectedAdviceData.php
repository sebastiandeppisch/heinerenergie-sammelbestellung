<?php

namespace App\Data;

use App\Enums\AdviceStatusResult;
use App\Enums\AdviceType;
use App\Enums\HouseType;
use App\Models\Advice;
use App\Models\User;
use App\Services\AdviceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[Typescript]
class DataProtectedAdviceData extends Data
{
    public function __construct(
        public string $id,
        public string $firstName,
        public string $lastName,
        public string $street,
        public string $streetNumber,
        public string $zip,
        public string $city,
        public ?string $email,
        public ?string $phone,
        public ?string $commentary,
        public ?string $advisor_id,
        public ?string $advice_status_id,
        public ?float $lng,
        public ?float $lat,
        public ?AdviceType $type,
        public Carbon $created_at,
        public Carbon $updated_at,
        public ?float $distance,
        public array $shares_ids,
        public ?string $placeNotes,
        public ?HouseType $houseType,
        public ?bool $landlordExists,
        public ?bool $helpType_place,
        public ?bool $helpType_technical,
        public ?bool $helpType_bureaucracy,
        public ?bool $helpType_other,
        public AdviceStatusResult $result,
        public ?bool $can_edit,
        public ?string $group_id,
    ) {}

    public static function fromModel(Advice $advice, ?User $user = null): self
    {
        $email = $advice->email;
        $phone = $advice->phone;
        if (! Auth::user()->can('view', $advice)) {
            $email = null;
            $phone = null;
        }

        $adviceService = app(AdviceService::class);

        return new self(
            id: $advice->uuid,
            firstName: $advice->firstName,
            lastName: $advice->lastName,
            street: $advice->street,
            streetNumber: $advice->streetNumber,
            zip: (string) $advice->zip,
            city: $advice->city,
            email: $email,
            phone: $phone,
            commentary: $advice->commentary,
            advisor_id: $advice->advisor?->uuid,
            advice_status_id: $advice->status?->uuid,
            lng: $advice->lng,
            lat: $advice->lat,
            type: $advice->type,
            created_at: $advice->created_at,
            updated_at: $advice->updated_at,
            distance: $user ? $adviceService->getDistance($advice, $user)?->getValue() : null,
            placeNotes: $advice->placeNotes,
            houseType: $advice->houseType,
            landlordExists: $advice->landlordExists,
            helpType_place: $advice->helpType_place,
            helpType_technical: $advice->helpType_technical,
            helpType_bureaucracy: $advice->helpType_bureaucracy,
            helpType_other: $advice->helpType_other,
            result: $advice->result,
            can_edit: $user ? $adviceService->canEdit($advice, $user) : false,
            group_id: $advice->group->uuid,
            shares_ids: $advice->shares_ids
        );
    }
}
