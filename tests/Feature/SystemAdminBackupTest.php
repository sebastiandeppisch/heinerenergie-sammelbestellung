<?php

declare(strict_types=1);

use App\Contracts\DatabaseDumperContract;
use App\Models\Group;
use App\Models\User;
use App\Services\DatabaseBackupService;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeDatabaseDumper;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');

    // The real dumper only speaks MySQL, while the suite also runs on SQLite.
    app()->bind(DatabaseDumperContract::class, fn (): FakeDatabaseDumper => new FakeDatabaseDumper);
});

/**
 * Signs in as one of the roles the application knows. "System admin" is not a
 * flag but a session mode a global admin switches into, so a global admin who
 * is currently acting as a group must be refused just like anybody else.
 */
function signInAs(string $role): void
{
    $session = app(SessionService::class);
    $group = Group::factory()->create();

    $user = match ($role) {
        'member' => User::factory()->create(['is_admin' => false]),
        'group admin' => User::factory()->create(['is_admin' => false]),
        'global admin acting as group' => User::factory()->create(['is_admin' => true]),
        'system admin' => User::factory()->create(['is_admin' => true]),
        default => throw new InvalidArgumentException('Unknown role '.$role),
    };

    $user->groups()->attach($group);

    match ($role) {
        'member' => $session->actAsGroup($group),
        'group admin' => $session->actAsGroup($group, asAdmin: true),
        'global admin acting as group' => $session->actAsGroup($group, asAdmin: true),
        'system admin' => $session->actAsSystemAdmin(),
    };

    test()->actingAs($user);
}

/**
 * Finished backups and unfinished dumps alike, but not the .htaccess that
 * guards the directory.
 *
 * @return list<string>
 */
function storedBackups(): array
{
    return array_values(array_filter(
        Storage::disk('local')->files('backups'),
        fn (string $file): bool => str_starts_with(basename($file), 'backup-')
    ));
}

it('sends a guest to the login instead of the backups', function (string $method, string $route): void {
    $this->call($method, route($route, ['backup' => 'backup-2026-09-08_120000-0123456789abcdef.sql.gz']))
        ->assertRedirect(route('login'));

    expect(storedBackups())->toBeEmpty();
})->with([
    ['get', 'system-admin'],
    ['post', 'system-admin.backups.create'],
    ['get', 'system-admin.backups.download'],
    ['delete', 'system-admin.backups.destroy'],
]);

it('refuses to create a backup for anybody who is not acting as system admin', function (string $role): void {
    signInAs($role);

    $this->post(route('system-admin.backups.create'))->assertForbidden();

    // The redirect alone would also happen on success, so the proof is that no
    // dump was written.
    expect(storedBackups())->toBeEmpty();
})->with(['member', 'group admin', 'global admin acting as group']);

it('refuses to hand out an existing backup to anybody who is not acting as system admin', function (string $role): void {
    signInAs('system admin');
    $this->post(route('system-admin.backups.create'));
    $name = basename(storedBackups()[0]);

    app(SessionService::class)->clear();
    signInAs($role);

    $this->get(route('system-admin.backups.download', ['backup' => $name]))->assertForbidden();
})->with(['member', 'group admin', 'global admin acting as group']);

it('refuses to delete a backup for anybody who is not acting as system admin', function (string $role): void {
    signInAs('system admin');
    $this->post(route('system-admin.backups.create'));
    $name = basename(storedBackups()[0]);

    app(SessionService::class)->clear();
    signInAs($role);

    $this->delete(route('system-admin.backups.destroy', ['backup' => $name]))->assertForbidden();

    expect(storedBackups())->toHaveCount(1);
})->with(['member', 'group admin', 'global admin acting as group']);

it('lets a system admin create a backup and lists it on the page', function (): void {
    signInAs('system admin');

    $this->post(route('system-admin.backups.create'))
        ->assertRedirect(route('system-admin'))
        ->assertSessionHas('success');

    expect(storedBackups())->toHaveCount(1);

    $this->get(route('system-admin'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('backupsSupported', true)
            ->has('backups', 1)
            ->where('backups.0.name', fn (string $name): bool => preg_match('/^backup-\d{4}-\d{2}-\d{2}_\d{6}-[0-9a-f]{16}\.sql\.gz$/', $name) === 1)
        );
});

it('lets a system admin download the gzipped dump', function (): void {
    signInAs('system admin');
    $this->post(route('system-admin.backups.create'));
    $name = basename(storedBackups()[0]);

    $response = $this->get(route('system-admin.backups.download', ['backup' => $name]));

    $response->assertOk()->assertDownload($name);

    expect(gzdecode($response->streamedContent()))->toBe(FakeDatabaseDumper::DUMP);
});

it('lets a system admin delete a backup', function (): void {
    signInAs('system admin');
    $this->post(route('system-admin.backups.create'));
    $name = basename(storedBackups()[0]);

    $this->delete(route('system-admin.backups.destroy', ['backup' => $name]))
        ->assertRedirect(route('system-admin'));

    expect(storedBackups())->toBeEmpty();
});

it('does not serve a file that this application never wrote as a backup', function (string $name): void {
    signInAs('system admin');
    Storage::disk('local')->put('backups/'.$name, 'secret');

    $this->get(route('system-admin.backups.download', ['backup' => $name]))->assertNotFound();
})->with([
    '.env',
    '.htaccess',
    'laravel.log',
    'backup-2026-09-08_120000-0123456789abcdef.sql.gz.txt',
    'unfinished dump' => 'backup-2026-09-08_120000-0123456789abcdef.sql.gz.part',
    'guessable name' => 'backup-2026-09-08_120000.sql.gz',
]);

it('reports an unsupported database instead of offering a backup', function (): void {
    app()->bind(DatabaseDumperContract::class, fn (): FakeDatabaseDumper => new FakeDatabaseDumper(supported: false));

    signInAs('system admin');

    $this->get(route('system-admin'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('backupsSupported', false));
});

it('leaves no half written dump behind when the dump fails', function (): void {
    app()->bind(DatabaseDumperContract::class, fn (): DatabaseDumperContract => new class implements DatabaseDumperContract
    {
        public function supportsCurrentConnection(): bool
        {
            return true;
        }

        public function dump(Closure $write): void
        {
            $write("-- first half\n");

            throw new RuntimeException('connection lost');
        }
    });

    signInAs('system admin');

    $this->post(route('system-admin.backups.create'))
        ->assertRedirect(route('system-admin'))
        ->assertSessionHas('error');

    // Covers the unfinished .part file as well.
    expect(storedBackups())->toBeEmpty();
});

it('does not offer a backup while its dump is still being written', function (): void {
    $listedDuringDump = null;
    $filesDuringDump = null;

    app()->instance(DatabaseDumperContract::class, new class(function () use (&$listedDuringDump, &$filesDuringDump): void {
        $listedDuringDump = app(DatabaseBackupService::class)->all();
        $filesDuringDump = storedBackups();
    }) implements DatabaseDumperContract
    {
        public function __construct(private readonly Closure $duringDump) {}

        public function supportsCurrentConnection(): bool
        {
            return true;
        }

        public function dump(Closure $write): void
        {
            $write("-- first half\n");
            ($this->duringDump)();
            $write("-- second half\n");
        }
    });

    signInAs('system admin');

    $this->post(route('system-admin.backups.create'));

    // A request killed by max_execution_time at this point leaves exactly
    // this behind: a file, but nothing that is offered as a backup.
    expect($filesDuringDump)->toHaveCount(1)
        ->and($filesDuringDump[0])->toEndWith('.sql.gz.part')
        ->and($listedDuringDump)->toBeEmpty()
        ->and(app(DatabaseBackupService::class)->all())->toHaveCount(1);
});

it('clears out the remains of dumps that died long ago', function (): void {
    $disk = Storage::disk('local');
    $stale = 'backups/backup-2026-09-08_120000-0123456789abcdef.sql.gz.part';
    $running = 'backups/backup-2026-09-08_130000-fedcba9876543210.sql.gz.part';
    $disk->put($stale, 'half a dump');
    $disk->put($running, 'half a dump');
    touch($disk->path($stale), now()->subHours(2)->getTimestamp());

    signInAs('system admin');

    $this->post(route('system-admin.backups.create'));

    // The recent one may still belong to a dump that is running right now.
    expect($disk->exists($stale))->toBeFalse()
        ->and($disk->exists($running))->toBeTrue();
});
