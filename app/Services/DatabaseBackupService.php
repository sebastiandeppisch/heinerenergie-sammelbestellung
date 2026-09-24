<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DatabaseDumperContract;
use App\Exceptions\DatabaseBackupException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Creates and manages gzipped database dumps for the system admin page.
 *
 * The dumps hold every password hash and every piece of personal data in the
 * application, so they are kept on the local disk below storage/app, never on
 * a disk that is reachable over HTTP.
 */
class DatabaseBackupService
{
    private const string DIRECTORY = 'backups';

    /**
     * Only names this service generated itself are ever opened again. Without
     * that check the download route, which by design may read anything, would
     * hand out arbitrary files.
     *
     * The random part keeps the name from being guessed. Should the web server
     * ever serve the storage directory directly, knowing roughly when a backup
     * was made must not be enough to download it.
     */
    private const string NAME_PATTERN = '/^backup-\d{4}-\d{2}-\d{2}_\d{6}-[0-9a-f]{16}\.sql\.gz$/';

    /**
     * A dump is written under this suffix and only renamed once it is
     * complete. A request killed by max_execution_time never reaches the
     * cleanup in create(), and what it leaves behind must not look like a
     * usable backup.
     */
    private const string PARTIAL_SUFFIX = '.part';

    /**
     * Remains of dumps that died this long ago are removed on the next run. No
     * request on shared hosting lives anywhere near that long.
     */
    private const int STALE_PARTIAL_SECONDS = 3600;

    public function __construct(private readonly DatabaseDumperContract $dumper) {}

    public function isSupported(): bool
    {
        return $this->dumper->supportsCurrentConnection();
    }

    /**
     * @return array{name: string, size: int, createdAt: string}
     */
    public function create(): array
    {
        $disk = $this->disk();
        $this->prepareDirectory($disk);
        $this->removeStalePartials($disk);

        $name = 'backup-'.now()->format('Y-m-d_His').'-'.bin2hex(random_bytes(8)).'.sql.gz';
        $partial = self::DIRECTORY.'/'.$name.self::PARTIAL_SUFFIX;
        $path = $disk->path($partial);

        $handle = gzopen($path, 'wb6');

        if ($handle === false) {
            throw DatabaseBackupException::couldNotWrite($path);
        }

        try {
            $this->dumper->dump(function (string $chunk) use ($handle, $path): void {
                // gzwrite reports the uncompressed bytes it took. A short write
                // means the disk is full, and silently truncating a backup is
                // the worst thing this service could do.
                if (gzwrite($handle, $chunk) !== strlen($chunk)) {
                    throw DatabaseBackupException::couldNotWrite($path);
                }
            });
        } catch (Throwable $e) {
            gzclose($handle);
            $disk->delete($partial);

            throw $e;
        }

        // gzclose writes the last compressed block, so a full disk may only
        // show up here.
        if (! gzclose($handle) || ! $disk->move($partial, self::DIRECTORY.'/'.$name)) {
            $disk->delete($partial);

            throw DatabaseBackupException::couldNotWrite($path);
        }

        return $this->describe($name);
    }

    /**
     * Newest first, which is the one an operator almost always wants.
     *
     * @return list<array{name: string, size: int, createdAt: string}>
     */
    public function all(): array
    {
        $backups = [];

        foreach ($this->disk()->files(self::DIRECTORY) as $file) {
            $name = basename($file);

            if (preg_match(self::NAME_PATTERN, $name) !== 1) {
                continue;
            }

            $backups[] = $this->describe($name);
        }

        usort($backups, fn (array $a, array $b): int => $b['name'] <=> $a['name']);

        return $backups;
    }

    /**
     * @return array{name: string, size: int, createdAt: string}
     */
    public function find(string $name): array
    {
        if (preg_match(self::NAME_PATTERN, $name) !== 1 || ! $this->disk()->exists(self::DIRECTORY.'/'.$name)) {
            throw DatabaseBackupException::unknownBackup($name);
        }

        return $this->describe($name);
    }

    /**
     * The absolute path of a backup, for streaming it to the browser.
     */
    public function path(string $name): string
    {
        $this->find($name);

        return $this->disk()->path(self::DIRECTORY.'/'.$name);
    }

    public function delete(string $name): void
    {
        $this->find($name);

        $this->disk()->delete(self::DIRECTORY.'/'.$name);
    }

    /**
     * @return array{name: string, size: int, createdAt: string}
     */
    private function describe(string $name): array
    {
        $disk = $this->disk();
        $path = self::DIRECTORY.'/'.$name;

        return [
            'name' => $name,
            'size' => $disk->size($path),
            'createdAt' => now()->setTimestamp($disk->lastModified($path))->toIso8601String(),
        ];
    }

    /**
     * Creates the directory with an .htaccess that denies all web access.
     *
     * The project root carries its own .htaccess for servers whose document
     * root points there instead of public/, but an installation updated
     * without that file would still serve the dumps. Web servers other than
     * Apache ignore both, which is what the unguessable names are for.
     */
    private function prepareDirectory(Filesystem $disk): void
    {
        $disk->makeDirectory(self::DIRECTORY);

        $disk->put(self::DIRECTORY.'/.htaccess', "Require all denied\n");
    }

    private function removeStalePartials(Filesystem $disk): void
    {
        $staleBefore = now()->getTimestamp() - self::STALE_PARTIAL_SECONDS;

        foreach ($disk->files(self::DIRECTORY) as $file) {
            if (! str_ends_with($file, self::PARTIAL_SUFFIX)) {
                continue;
            }

            $name = basename($file, self::PARTIAL_SUFFIX);

            if (preg_match(self::NAME_PATTERN, $name) === 1 && $disk->lastModified($file) < $staleBefore) {
                $disk->delete($file);
            }
        }
    }

    private function disk(): Filesystem
    {
        return Storage::disk('local');
    }
}
