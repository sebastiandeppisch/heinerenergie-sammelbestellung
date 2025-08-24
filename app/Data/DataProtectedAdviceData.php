<?php

namespace App\Data;

use App\Enums\AdviceStatusResult;
use App\Enums\AdviceType;
use App\Enums\HouseType;
use App\Models\Advice;
use App\Models\User;
use App\Services\AdviceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[Typescript]
class DataProtectedAdviceData extends Data
{
    public function __construct(
        public string $id,
        public string $first_name,
        public string $last_name,
        public string $street,
        public string $street_number,
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
        public Collection $shares_ids,
        public ?string $place_notes,
        public ?HouseType $house_type,
        public ?bool $landlord_exists,
        public ?bool $help_type_place,
        public ?bool $help_type_technical,
        public ?bool $help_type_bureaucracy,
        public ?bool $help_type_other,
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
            first_name: $advice->first_name,
            last_name: $advice->last_name,
            street: $advice->street,
            street_number: $advice->street_number,
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
            place_notes: $advice->place_notes,
            house_type: $advice->house_type,
            landlord_exists: $advice->landlord_exists,
            help_type_place: $advice->help_type_place,
            help_type_technical: $advice->help_type_technical,
            help_type_bureaucracy: $advice->help_type_bureaucracy,
            help_type_other: $advice->help_type_other,
            result: $advice->result,
            can_edit: $user ? $adviceService->canEdit($advice, $user) : false,
            group_id: $advice->group->uuid,
            shares_ids: $advice->shares_ids
        );
    }
}
