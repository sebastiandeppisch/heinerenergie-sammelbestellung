<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UserData extends Data
{
    public function __construct(
        public string $id,
        public string $first_name,
        public string $last_name,
        public string $name,
        public string $email,
        public bool $is_acting_as_admin,
        public ?float $lat,
        public ?float $long,
        public ?string $profile_picture,
        public ?int $advice_radius,
        public ?string $street,
        public ?string $streetNumber,
        public ?string $city,
        public ?string $zip,
        public bool $is_admin,
    ) {}

    static public function fromModel(User $user, bool $isActingAsAdmin){
        return new self(
            $user->id,
            $user->first_name,
            $user->last_name,
            $user->name,
            $user->email,
            $isActingAsAdmin,
            $user->coordinate?->lat,
            $user->coordinate?->lng,
            null, //TODO add missing profile_picture
            $user->advice_radius,
            $user->street,
            $user->streetNumber,
            $user->city,
            $user->zip,
            $user->is_admin
        );
    }
}
