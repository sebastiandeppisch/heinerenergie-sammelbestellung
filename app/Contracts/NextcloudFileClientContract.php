<?php

namespace App\Contracts;

use App\Nextcloud\Data\NextcloudDir;
use App\Nextcloud\Data\NextcloudFile;

interface NextcloudFileClientContract
{
    public function resolveFileId(string $fileId): string;

    /** @return array<NextcloudDir|NextcloudFile> */
    public function dirListing(string $fileIdOrPath): array;

    /** @return array<NextcloudDir> */
    public function searchDirs(string $rootPath, string $slugSubstring): array;

    /** @return resource */
    public function downloadFile(string $fileId): mixed;

    public function createDir(string $parentPath, string $name): NextcloudDir;

    /** @param resource $stream */
    public function uploadFile(string $parentPath, string $name, mixed $stream): NextcloudFile;
}
