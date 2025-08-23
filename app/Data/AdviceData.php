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
        public string $first_name,
        public string $last_name,
        public string $street,
        public string $street_number,
        public string $zip,
        public string $city,
        public string $email,
        public string $phone,
        public ?string $commentary,
        public ?string $advisor_id,
        public ?string $advice_status_id,
        public ?float $lng,
        public ?float $lat,
        public AdviceType $type,
        public bool $help_type_place,
        public bool $help_type_technical,
        public bool $help_type_bureaucracy,
        public bool $help_type_other,
        public ?HouseType $house_type,
        public ?bool $landlord_exists,
        public ?string $place_notes,
        public array $shares_ids,
        public ?float $distance = null,
        public bool $can_edit = false,
    ) {}

    public static function fromModel(Advice $advice, ?User $user = null): self
    {
        $adviceService = app(AdviceService::class);

        return new self(
            first_name: $advice->first_name,
            last_name: $advice->last_name,
            street: $advice->street,
            street_number: $advice->street_number,
            zip: $advice->zip,
            city: $advice->city,
            email: $advice->email,
            phone: $advice->phone,
            commentary: $advice->commentary,
            advisor_id: $advice->advisor?->uuid,
            advice_status_id: $advice->status?->uuid,
            lng: $advice->lng,
            lat: $advice->lat,
            type: $advice->type,
            help_type_place: $advice->help_type_place,
            help_type_technical: $advice->help_type_technical,
            help_type_bureaucracy: $advice->help_type_bureaucracy,
            help_type_other: $advice->help_type_other,
            house_type: $advice->house_type,
            landlord_exists: $advice->landlord_exists,
            place_notes: $advice->place_notes,
            shares_ids: $advice->shares_ids,
            distance: $user ? $adviceService->getDistance($advice, $user)?->getValue() : null,
            can_edit: $user ? $adviceService->canEdit($advice, $user) : false,
        );
    }
}
