<?php

namespace App\Data;

use App\Models\User;
use Illuminate\Support\Collection;
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
        public ?string $street_number,
        public ?string $city,
        public ?string $zip,
        public bool $is_admin,
        /** @var Collection<GroupBaseData> */
        public Collection $groups,
    ) {}

    public static function fromModel(User $user, bool $isActingAsAdmin, bool $withGroups = false)
    {
        $groups = collect();
        if ($withGroups && $user->relationLoaded('groups')) {
            $groups = $user->groups->map(fn ($group) => GroupBaseData::fromModel($group));
        }

        return new self(
            id: $user->uuid,
            first_name: $user->first_name,
            last_name: $user->last_name,
            name: $user->name,
            email: $user->email,
            is_acting_as_admin: $isActingAsAdmin,
            lat: $user->coordinate?->lat,
            long: $user->coordinate?->lng,
            profile_picture: null, // TODO add missing profile_picture
            advice_radius: $user->advice_radius,
            street: $user->street,
            street_number: $user->street_number,
            city: $user->city,
            zip: $user->zip,
            is_admin: $user->is_admin,
            groups: $groups
        );
    }
}
