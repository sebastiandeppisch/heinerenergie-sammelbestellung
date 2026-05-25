<?php

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
     * Resolve the current Nextcloud path for an advice folder.
     * Heals the stored path if it has changed (e.g. folder moved in Nextcloud).
     *
     * @throws RuntimeException when the folder cannot be found via path or fileId
     */
    public function resolve(Advice $advice): string
    {
        if (! $advice->nextcloud_folder_id) {
            throw new RuntimeException('Advice has no linked Nextcloud folder.');
        }

        // Primary: try stored path and verify fileId matches
        if ($advice->nextcloud_folder_path) {
            try {
                $currentPath = $this->client->resolveFileId($advice->nextcloud_folder_id);

                if ($currentPath !== $advice->nextcloud_folder_path) {
                    // Self-heal: path changed (folder moved), update stored path
                    $advice->update(['nextcloud_folder_path' => $currentPath]);
                }

                return $currentPath;
            } catch (RuntimeException) {
                // Fall through to fileId-based lookup
            }
        }

        // Fallback: resolve via fileId only
        $path = $this->client->resolveFileId($advice->nextcloud_folder_id);
        $advice->update(['nextcloud_folder_path' => $path]);

        return $path;
    }
}
