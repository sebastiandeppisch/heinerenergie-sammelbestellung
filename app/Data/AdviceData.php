<?php

namespace App\Data;

use App\Enums\AdviceType;
use App\Enums\HouseType;
use App\Models\Advice;
use App\Models\User;
use App\Services\AdviceService;
use Spatie\LaravelData\Data;

class AdviceData extends Data
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $street,
        public string $streetNumber,
        public int $zip,
        public string $city,
        public string $email,
        public string $phone,
        public ?string $commentary,
        public ?int $advisor_id,
        public ?int $advice_status_id,
        public ?float $lng,
        public ?float $lat,
        public AdviceType $type,
        public bool $helpType_place,
        public bool $helpType_technical,
        public bool $helpType_bureaucracy,
        public bool $helpType_other,
        public ?HouseType $houseType,
        public ?bool $landlordExists,
        public ?string $placeNotes,
        public array $shares_ids,
        public ?float $distance = null,
        public bool $can_edit = false,
    ) {}

    public static function fromModel(Advice $advice, ?User $user = null): self
    {
        $adviceService = app(AdviceService::class);

        return new self(
            firstName: $advice->firstName,
            lastName: $advice->lastName,
            street: $advice->street,
            streetNumber: $advice->streetNumber,
            zip: $advice->zip,
            city: $advice->city,
            email: $advice->email,
            phone: $advice->phone,
            commentary: $advice->commentary,
            advisor_id: $advice->advisor?->uuid,
            advice_status_id: $advice->advice_status?->uuid,
            lng: $advice->lng,
            lat: $advice->lat,
            type: $advice->type,
            helpType_place: $advice->helpType_place,
            helpType_technical: $advice->helpType_technical,
            helpType_bureaucracy: $advice->helpType_bureaucracy,
            helpType_other: $advice->helpType_other,
            houseType: $advice->houseType,
            landlordExists: $advice->landlordExists,
            placeNotes: $advice->placeNotes,
            shares_ids: $advice->shares_ids,
            distance: $user ? $adviceService->getDistance($advice, $user)?->getValue() : null,
            can_edit: $user ? $adviceService->canEdit($advice, $user) : false,
        );
    }
}
