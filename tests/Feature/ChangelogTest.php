<?php

use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use App\Services\VersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

const TEMP_DIR = 'changelog-test';

afterEach(function (): void {
    foreach (glob(sys_get_temp_dir().'/'.TEMP_DIR.'/*.tmp') ?: [] as $path) {
        @unlink($path);
    }
});

function tempFile(string $name, string $contents): string
{
    $directory = sys_get_temp_dir().'/'.TEMP_DIR;

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $path = $directory.'/'.uniqid($name).'.tmp';
    file_put_contents($path, $contents);

    return $path;
}

test('the version is read from the version file', function (): void {
    $versionFile = tempFile('version', (string) json_encode(['version' => '2026-08.1', 'commit' => 'abc1234']));

    $service = new VersionService(versionFile: $versionFile);

    expect($service->version())->toBe('2026-08.1');
});

test('the newest changelog version is used when no version file exists', function (): void {
    $changelog = tempFile('changelog', "# Changelog\n\n## [2026-08.1] – 2026-08-19\n\n- Etwas Neues\n\n## [2026-04.1] – 2026-04-01\n");

    $service = new VersionService(versionFile: '/does/not/exist.json', changelogFile: $changelog);

    expect($service->version())->toBe('2026-08.1');
});

test('the version falls back to dev without any source', function (): void {
    $service = new VersionService(versionFile: '/does/not/exist.json', changelogFile: '/does/not/exist.md');

    expect($service->version())->toBe('dev');
});

test('the changelog is rendered as html without raw markup', function (): void {
    $changelog = tempFile('changelog', "## [2026-08.1] – 2026-08-19\n\n- **Neu.** Etwas <script>alert(1)</script>\n");

    $service = new VersionService(changelogFile: $changelog);

    expect($service->changelogHtml())
        ->toContain('<h2>')
        ->toContain('<strong>Neu.</strong>')
        ->not->toContain('<script>');
});

test('issue references are not rendered in the gui', function (): void {
    $changelog = tempFile('changelog', "## [2026-08.1] – 2026-08-19\n\n- **Neu.** Etwas Neues #52 #55\n- Ein Fehler weniger. #62\n");

    $service = new VersionService(changelogFile: $changelog);

    expect($service->changelogHtml())
        ->toContain('Etwas Neues')
        ->toContain('Ein Fehler weniger.')
        ->not->toContain('#52')
        ->not->toContain('#55')
        ->not->toContain('#62');
});

test('the changelog endpoint returns the version and the rendered changelog', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('api.changelog'));

    $response->assertOk()
        ->assertJsonStructure(['version', 'changelog']);

    expect($response->json('changelog'))->toContain('<h1>Changelog</h1>');
});

test('guests can not access the changelog endpoint', function (): void {
    $this->getJson(route('api.changelog'))->assertUnauthorized();
});

test('the version is shared with every page', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->users()->attach($user->id, ['is_admin' => false]);

    app(SessionService::class)->actAsGroup($group);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page): Assert => $page->where('version', app(VersionService::class)->version()));
});
