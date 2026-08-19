<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Provides the deployed application version and the changelog shown in the UI.
 *
 * The version is baked into `version.json` when the deployment package is built,
 * so it is available on servers without git. During development the file is
 * missing and the newest changelog heading is used instead.
 */
class VersionService
{
    private const DEVELOPMENT_VERSION = 'dev';

    public function __construct(
        private ?string $versionFile = null,
        private ?string $changelogFile = null,
    ) {}

    public function version(): string
    {
        return $this->versionFromFile()
            ?? $this->latestChangelogVersion()
            ?? self::DEVELOPMENT_VERSION;
    }

    public function changelogHtml(): ?string
    {
        $changelog = $this->changelog();

        if ($changelog === null) {
            return null;
        }

        return Str::markdown($this->withoutIssueReferences($changelog), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * Strips the trailing issue references (`#58 #62`) that only serve as links on GitHub.
     */
    private function withoutIssueReferences(string $changelog): string
    {
        return (string) preg_replace('/(\s*#\d+)+\s*$/m', '', $changelog);
    }

    private function versionFromFile(): ?string
    {
        $path = $this->versionFile ?? base_path('version.json');

        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $data = json_decode($contents, true);

        if (! is_array($data) || ! isset($data['version']) || ! is_string($data['version'])) {
            return null;
        }

        $version = trim($data['version']);

        return $version === '' ? null : $version;
    }

    /**
     * Reads the newest version from a heading like `## [2026-08.1] – 2026-08-19`.
     */
    private function latestChangelogVersion(): ?string
    {
        $changelog = $this->changelog();

        if ($changelog === null) {
            return null;
        }

        if (preg_match('/^##\s*\[([^\]]+)\]/m', $changelog, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function changelog(): ?string
    {
        $path = $this->changelogFile ?? base_path('CHANGELOG.md');

        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        return $contents === false ? null : $contents;
    }
}
