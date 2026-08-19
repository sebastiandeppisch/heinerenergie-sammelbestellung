<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class NextcloudGroupUserData extends Data
{
    public function __construct(
        public ?string $nc_id,
        public ?string $nc_email,
        public ?string $nc_displayname,
        public ?bool $nc_enabled,
        public ?CrmUserData $crm_user,
        public ?bool $crm_is_group_member,
    ) {}
}
