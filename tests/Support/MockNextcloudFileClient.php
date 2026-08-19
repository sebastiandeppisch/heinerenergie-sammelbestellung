<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\NextcloudFileClientContract;
use App\Nextcloud\Data\NextcloudDir;
use App\Nextcloud\Data\NextcloudFile;
use Carbon\Carbon;

class MockNextcloudFileClient implements NextcloudFileClientContract
{
    /** @var list<NextcloudDir> */
    private array $dirs;

    /** @var list<NextcloudFile> */
    private array $files;

    private int $nextId = 100;

    public function __construct()
    {
        $this->dirs = [
            new NextcloudDir('1', '/Beratungen', 'Beratungen'),
            new NextcloudDir('2', '/Beratungen/Offen', 'Offen'),
            new NextcloudDir('3', '/Beratungen/Fertig', 'Fertig'),
            new NextcloudDir('10', '/Beratungen/Offen/2024-01-15_beratung-mueller', '2024-01-15_beratung-mueller'),
            new NextcloudDir('11', '/Beratungen/Fertig/2023-12-01_beratung-schmidt', '2023-12-01_beratung-schmidt'),
        ];

        $this->files = [
            new NextcloudFile('20', '/Beratungen/Offen/2024-01-15_beratung-mueller/dokument.pdf', 'dokument.pdf', 102400, 'application/pdf', Carbon::now()->subDays(5)),
            new NextcloudFile('21', '/Beratungen/Offen/2024-01-15_beratung-mueller/foto.jpg', 'foto.jpg', 512000, 'image/jpeg', Carbon::now()->subDays(3)),
        ];
    }

    public function folderExists(string $path): bool
    {
        return array_any($this->dirs, fn (NextcloudDir $dir): bool => $dir->path === $path);
    }

    public function dirListing(string $path): array
    {
        $path = rtrim($path, '/') ?: '/';

        $result = [];

        foreach ($this->dirs as $dir) {
            if (dirname($dir->path) === $path && $dir->path !== $path) {
                $result[] = $dir;
            }
        }

        foreach ($this->files as $file) {
            if (dirname($file->path) === $path) {
                $result[] = $file;
            }
        }

        return $result;
    }

    /**
     * @return NextcloudDir[]
     */
    public function searchDirs(string $rootPath, string $slugSubstring): array
    {
        $rootPath = rtrim($rootPath, '/');
        $result = [];

        foreach ($this->dirs as $dir) {
            if (str_starts_with($dir->path, $rootPath.'/') && str_contains($dir->name, $slugSubstring)) {
                $result[] = $dir;
            }
        }

        return $result;
    }

    public function downloadFile(string $path): mixed
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "Mock file content for path: {$path}");
        rewind($stream);

        return $stream;
    }

    public function createDir(string $parentPath, string $name): NextcloudDir
    {
        $dir = new NextcloudDir(
            fileId: (string) $this->nextId++,
            path: rtrim($parentPath, '/').'/'.$name,
            name: $name,
        );

        $this->dirs[] = $dir;

        return $dir;
    }

    public function uploadFile(string $parentPath, string $name, mixed $stream): NextcloudFile
    {
        $content = stream_get_contents($stream);

        $file = new NextcloudFile(
            fileId: (string) $this->nextId++,
            path: rtrim($parentPath, '/').'/'.$name,
            name: $name,
            size: strlen($content),
            mimeType: 'application/octet-stream',
            lastModified: Carbon::now(),
        );

        $this->files[] = $file;

        return $file;
    }
}
