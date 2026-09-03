<?php

declare(strict_types=1);

namespace App\Nextcloud;

class NextcloudConfig
{
    public function isConfigured(): bool
    {
        return filled(config('nextcloud.base_url')) && filled(config('nextcloud.username'));
    }
}
