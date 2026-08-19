<?php

declare(strict_types=1);

namespace App\Nextcloud;

use App\Contracts\NextcloudFileClientContract;
use App\Models\Advice;
use RuntimeException;

class NextcloudFolderResolver
{
    public function __construct(
        private readonly NextcloudFileClientContract $client,
    ) {}

    /**
     * @throws RuntimeException when the linked folder is missing or not found in Nextcloud
     */
    public function resolve(Advice $advice): string
    {
        if (! $advice->nextcloud_folder_id || ! $advice->nextcloud_folder_path) {
            throw new RuntimeException('Advice has no linked Nextcloud folder.');
        }

        if (! $this->client->folderExists($advice->nextcloud_folder_path)) {
            throw new RuntimeException('Linked Nextcloud folder not found.');
        }

        return $advice->nextcloud_folder_path;
    }
}
