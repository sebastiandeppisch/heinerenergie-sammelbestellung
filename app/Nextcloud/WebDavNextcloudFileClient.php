<?php

namespace App\Nextcloud;

use App\Contracts\NextcloudFileClientContract;
use App\Nextcloud\Data\NextcloudDir;
use App\Nextcloud\Data\NextcloudFile;
use App\Services\CurrentGroupService;
use Carbon\Carbon;
use RuntimeException;
use Sabre\DAV\Client;
use Sabre\DAV\Xml\Property\ResourceType;
use Sabre\HTTP\ClientHttpException;

class WebDavNextcloudFileClient implements NextcloudFileClientContract
{
    private Client $client;

    private string $username;

    public function __construct(private readonly CurrentGroupService $groupService)
    {
        $baseUrl = config('nextcloud.base_url');
        $this->username = config('nextcloud.username');
        $password = config('nextcloud.password');

        $this->client = new Client([
            'baseUri' => rtrim($baseUrl, '/').'/remote.php/dav/files/'.rawurlencode($this->username).'/',
            'userName' => $this->username,
            'password' => $password,
        ]);
    }

    public function folderExists(string $path): bool
    {
        $absPath = $this->absolutePath($path);

        try {
            $props = $this->client->propFind($this->toSabrePath($absPath), [
                '{DAV:}resourcetype',
            ], 0);

            $resourceType = $props['{DAV:}resourcetype'] ?? null;

            return $resourceType instanceof ResourceType && $resourceType->is('{DAV:}collection');
        } catch (ClientHttpException) {
            return false;
        }
    }

    public function dirListing(string $path): array
    {
        $absPath = $this->absolutePath($path);

        try {
            $props = $this->client->propFind($this->toSabrePath($absPath), [
                '{DAV:}displayname',
                '{DAV:}resourcetype',
                '{DAV:}getcontentlength',
                '{DAV:}getcontenttype',
                '{DAV:}getlastmodified',
                '{http://owncloud.org/ns}fileid',
            ], 1);
        } catch (ClientHttpException $e) {
            throw new RuntimeException("Cannot list directory {$path}: HTTP {$e->getHttpStatus()}", 0, $e);
        }

        $selfPath = $this->normalizePath($absPath);
        $items = [];

        foreach ($props as $href => $p) {
            $entryAbsPath = $this->hrefToPath($href);

            if ($this->normalizePath($entryAbsPath) === $selfPath) {
                continue;
            }

            $entryRelPath = $this->relativePath($entryAbsPath);
            $resourceType = $p['{DAV:}resourcetype'] ?? null;
            $isDir = $resourceType instanceof ResourceType && $resourceType->is('{DAV:}collection');
            $fileId = (string) ($p['{http://owncloud.org/ns}fileid'] ?? '');
            $name = $p['{DAV:}displayname'] ?? basename(trim($entryRelPath, '/'));

            if ($isDir) {
                $items[] = new NextcloudDir(
                    fileId: $fileId,
                    path: $entryRelPath,
                    name: $name,
                );
            } else {
                $items[] = new NextcloudFile(
                    fileId: $fileId,
                    path: $entryRelPath,
                    name: $name,
                    size: (int) ($p['{DAV:}getcontentlength'] ?? 0),
                    mimeType: $p['{DAV:}getcontenttype'] ?? 'application/octet-stream',
                    lastModified: Carbon::parse($p['{DAV:}getlastmodified'] ?? 'now'),
                );
            }
        }

        return $items;
    }

    public function searchDirs(string $rootPath, string $slugSubstring): array
    {
        $absRoot = $this->absolutePath($rootPath);

        try {
            $props = $this->client->propFind($this->toSabrePath($absRoot), [
                '{DAV:}displayname',
                '{DAV:}resourcetype',
                '{http://owncloud.org/ns}fileid',
            ], 'infinity');
        } catch (ClientHttpException $e) {
            throw new RuntimeException("Cannot search in {$rootPath}: HTTP {$e->getHttpStatus()}", 0, $e);
        }

        $selfPath = $this->normalizePath($absRoot);
        $items = [];

        foreach ($props as $href => $p) {
            $resourceType = $p['{DAV:}resourcetype'] ?? null;
            if (! ($resourceType instanceof ResourceType && $resourceType->is('{DAV:}collection'))) {
                continue;
            }

            $entryAbsPath = $this->hrefToPath($href);
            if ($this->normalizePath($entryAbsPath) === $selfPath) {
                continue;
            }

            $name = $p['{DAV:}displayname'] ?? basename(trim($entryAbsPath, '/'));
            if (! str_contains(strtolower($name), strtolower($slugSubstring))) {
                continue;
            }

            $items[] = new NextcloudDir(
                fileId: (string) ($p['{http://owncloud.org/ns}fileid'] ?? ''),
                path: $this->relativePath($entryAbsPath),
                name: $name,
            );
        }

        return $items;
    }

    public function downloadFile(string $path): mixed
    {
        $absPath = $this->absolutePath($path);

        try {
            $response = $this->client->request('GET', $this->toSabrePath($absPath));
        } catch (ClientHttpException $e) {
            throw new RuntimeException("Cannot download {$path}: HTTP {$e->getHttpStatus()}", 0, $e);
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $response['body']);
        rewind($stream);

        return $stream;
    }

    public function createDir(string $parentPath, string $name): NextcloudDir
    {
        $absParent = $this->absolutePath($parentPath);
        $absNewPath = rtrim($absParent, '/').'/'.$name;

        try {
            $response = $this->client->request('MKCOL', $this->toSabrePath($absNewPath));
        } catch (ClientHttpException $e) {
            throw new RuntimeException("Cannot create directory {$absNewPath}: HTTP {$e->getHttpStatus()}", 0, $e);
        }

        if ($response['statusCode'] !== 201) {
            throw new RuntimeException("Cannot create directory {$absNewPath}: HTTP {$response['statusCode']}");
        }

        $props = $this->client->propFind($this->toSabrePath($absNewPath), [
            '{http://owncloud.org/ns}fileid',
        ], 0);

        return new NextcloudDir(
            fileId: (string) ($props['{http://owncloud.org/ns}fileid'] ?? ''),
            path: $this->relativePath($absNewPath),
            name: $name,
        );
    }

    public function uploadFile(string $parentPath, string $name, mixed $stream): NextcloudFile
    {
        $absParent = $this->absolutePath($parentPath);
        $absUploadPath = rtrim($absParent, '/').'/'.$name;
        $content = stream_get_contents($stream);

        try {
            $response = $this->client->request('PUT', $this->toSabrePath($absUploadPath), $content, [
                'Content-Type' => 'application/octet-stream',
            ]);
        } catch (ClientHttpException $e) {
            throw new RuntimeException("Cannot upload {$absUploadPath}: HTTP {$e->getHttpStatus()}", 0, $e);
        }

        if (! in_array($response['statusCode'], [200, 201, 204])) {
            throw new RuntimeException("Cannot upload {$absUploadPath}: HTTP {$response['statusCode']}");
        }

        $props = $this->client->propFind($this->toSabrePath($absUploadPath), [
            '{DAV:}getcontentlength',
            '{DAV:}getcontenttype',
            '{DAV:}getlastmodified',
            '{http://owncloud.org/ns}fileid',
        ], 0);

        return new NextcloudFile(
            fileId: (string) ($props['{http://owncloud.org/ns}fileid'] ?? ''),
            path: $this->relativePath($absUploadPath),
            name: $name,
            size: (int) ($props['{DAV:}getcontentlength'] ?? strlen($content)),
            mimeType: $props['{DAV:}getcontenttype'] ?? 'application/octet-stream',
            lastModified: Carbon::parse($props['{DAV:}getlastmodified'] ?? 'now'),
        );
    }

    private function basePath(): string
    {
        return $this->groupService->getGroup()?->nextcloud_search_path ?? '/';
    }

    private function absolutePath(string $relativePath): string
    {
        $base = rtrim($this->basePath(), '/');
        $relative = '/'.trim($relativePath, '/');

        if ($base === '') {
            return $relative;
        }

        if ($relative === '/') {
            return $base;
        }

        return $base.$relative;
    }

    private function relativePath(string $absolutePath): string
    {
        $base = rtrim($this->basePath(), '/');

        if ($base === '') {
            return '/'.trim($absolutePath, '/');
        }

        $normalized = '/'.trim($absolutePath, '/');

        if ($normalized === $base) {
            return '/';
        }

        if (str_starts_with($normalized, $base.'/')) {
            return substr($normalized, strlen($base));
        }

        return $normalized;
    }

    private function toSabrePath(string $path): string
    {
        $segments = array_filter(explode('/', $path), fn ($s) => $s !== '');

        return implode('/', array_map('rawurlencode', $segments));
    }

    private function hrefToPath(string $href): string
    {
        $decoded = rawurldecode($href);
        $prefix = '/remote.php/dav/files/'.$this->username;

        if (str_starts_with($decoded, $prefix)) {
            return '/'.trim(substr($decoded, strlen($prefix)), '/');
        }

        return $decoded;
    }

    private function normalizePath(string $path): string
    {
        return '/'.trim($path, '/');
    }
}
