<?php

namespace App\Data;

use App\Enums\AdviceStatusResult;
use App\Enums\AdviceType;
use App\Enums\HouseType;
use App\Models\Advice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[Typescript]
class DataProtectedAdviceData extends Data
{
    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        public string $street,
        public string $streetNumber,
        public string $zip,
        public string $city,
        public string $email,
        public string $phone,
        public ?string $commentary,
        public ?int $advisor_id,
        public ?int $advice_status_id,
        public ?float $long,
        public ?float $lat,
        public ?AdviceType $type,
        public Carbon $created_at,
        public Carbon $updated_at,
        public ?float $distance,
        public array $shares_ids,
        public ?string $placeNotes,
        public ?HouseType $houseType,
        public ?bool $landlordExists,
        public ?string $helpType_place,
        public ?string $helpType_technical,
        public ?string $helpType_bureaucracy,
        public ?string $helpType_other,
        public ?AdviceStatusResult $result,
        public ?bool $can_edit,
        public ?int $group_id,
    ) {}

    public static function fromModel(Advice $advice): self
    {
        $email = $advice->email;
        $phone = $advice->phone;
        if (! Auth::user()->can('view', $advice)) {
            $email = null;
            $phone = null;
        }

        return new self(
            id: $advice->id,
            firstName: $advice->firstName,
            lastName: $advice->lastName,
            street: $advice->street,
            streetNumber: $advice->streetNumber,
            zip: $advice->zip,
            city: $advice->city,
            email: $email,
            phone: $phone,
            commentary: $advice->commentary,
            advisor_id: $advice->advisor_id,
            advice_status_id: $advice->advice_status_id,
            long: $advice->long,
            lat: $advice->lat,
            type: $advice->type,
            created_at: $advice->created_at,
            updated_at: $advice->updated_at,
            distance: $advice->distance,
            shares_ids: $advice->shares_ids,
            placeNotes: $advice->placeNotes,
            houseType: $advice->houseType,
            landlordExists: $advice->landlordExists,
            helpType_place: $advice->helpType_place,
            helpType_technical: $advice->helpType_technical,
            helpType_bureaucracy: $advice->helpType_bureaucracy,
            helpType_other: $advice->helpType_other,
            result: $advice->result,
            can_edit: $advice->can_edit,
            group_id: $advice->group_id,
        );
    }
}
