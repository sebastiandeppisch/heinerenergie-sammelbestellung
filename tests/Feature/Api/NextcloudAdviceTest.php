<?php

use App\Contracts\NextcloudFileClientContract;
use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\MockNextcloudFileClient;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create(['nextcloud_search_path' => '/Beratungen']);
    $this->group->users()->attach($this->user->id, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);

    $this->advice = Advice::factory()->create([
        'advisor_id' => $this->user->id,
        'group_id' => $this->group->id,
    ]);

    app()->bind(NextcloudFileClientContract::class, MockNextcloudFileClient::class);

    Config::set('app.group_context', 'global');

    $this->withHeaders(['Accept' => 'application/json']);
});

describe('search', function () {
    test('can search for nextcloud folders', function () {
        actingAs($this->user)
            ->get("/api/advices/{$this->advice->uuid}/nextcloud/search?q=mueller")
            ->assertOk()
            ->assertJsonIsArray();
    });

    test('unauthenticated user cannot search nextcloud folders', function () {
        $this->get("/api/advices/{$this->advice->uuid}/nextcloud/search?q=mueller")
            ->assertUnauthorized();
    });
});

describe('browse', function () {
    test('browsing returns path and items', function () {
        actingAs($this->user)
            ->get("/api/advices/{$this->advice->uuid}/nextcloud/browse?path=/")
            ->assertOk()
            ->assertJsonStructure(['path', 'items'])
            ->assertJsonFragment(['path' => '/'])
            ->assertJsonFragment(['name' => 'Beratungen']);
    });

    test('browsing without path defaults to root', function () {
        actingAs($this->user)
            ->get("/api/advices/{$this->advice->uuid}/nextcloud/browse")
            ->assertOk()
            ->assertJsonFragment(['path' => '/']);
    });

    test('browsing with path traversal returns 403', function () {
        actingAs($this->user)
            ->get("/api/advices/{$this->advice->uuid}/nextcloud/browse?path=/..")
            ->assertForbidden();
    });

    test('browsing a nested directory returns its children', function () {
        actingAs($this->user)
            ->get("/api/advices/{$this->advice->uuid}/nextcloud/browse?path=/Beratungen/Offen")
            ->assertOk()
            ->assertJsonFragment(['name' => '2024-01-15_beratung-mueller']);
    });
});

describe('createFolder', function () {
    test('can create a nextcloud folder and link it', function () {
        actingAs($this->user)
            ->post("/api/advices/{$this->advice->uuid}/nextcloud/folder", [
                'name' => 'beratung-test',
                'parent_path' => '/Offen',
            ])
            ->assertOk()
            ->assertJsonStructure(['fileId', 'path', 'name']);

        $this->advice->refresh();
        expect($this->advice->nextcloud_folder_id)->not->toBeNull();
        expect($this->advice->nextcloud_folder_path)->toContain('beratung-test');
    });

    test('createFolder requires name and parent_path', function () {
        actingAs($this->user)
            ->post("/api/advices/{$this->advice->uuid}/nextcloud/folder", ['name' => 'test'])
            ->assertUnprocessable();

        actingAs($this->user)
            ->post("/api/advices/{$this->advice->uuid}/nextcloud/folder", ['parent_path' => '/Beratungen/Offen'])
            ->assertUnprocessable();
    });

    test('unauthorized user cannot create folder', function () {
        $otherUser = User::factory()->create();

        actingAs($otherUser)
            ->post("/api/advices/{$this->advice->uuid}/nextcloud/folder", ['name' => 'test'])
            ->assertForbidden();
    });
});

describe('link', function () {
    test('can link an existing nextcloud folder', function () {
        actingAs($this->user)
            ->post("/api/advices/{$this->advice->uuid}/nextcloud/link", [
                'fileId' => '42',
                'path' => '/Offen/beratung-test',
            ])
            ->assertOk()
            ->assertJson(['fileId' => '42', 'path' => '/Offen/beratung-test']);

        $this->advice->refresh();
        expect($this->advice->nextcloud_folder_id)->toBe('42');
        expect($this->advice->nextcloud_folder_path)->toBe('/Offen/beratung-test');
    });

    test('link requires fileId and path', function () {
        actingAs($this->user)
            ->post("/api/advices/{$this->advice->uuid}/nextcloud/link", ['fileId' => '42'])
            ->assertUnprocessable();
    });
});

describe('unlink', function () {
    test('can unlink a nextcloud folder', function () {
        $this->advice->update([
            'nextcloud_folder_id' => '10',
            'nextcloud_folder_path' => '/Beratungen/Offen/2024-01-15_beratung-mueller',
        ]);

        actingAs($this->user)
            ->delete("/api/advices/{$this->advice->uuid}/nextcloud/link")
            ->assertNoContent();

        $this->advice->refresh();
        expect($this->advice->nextcloud_folder_id)->toBeNull();
        expect($this->advice->nextcloud_folder_path)->toBeNull();
    });
});

describe('upload', function () {
    test('can upload a file to linked nextcloud folder', function () {
        $this->advice->update([
            'nextcloud_folder_id' => '10',
            'nextcloud_folder_path' => '/Beratungen/Offen/2024-01-15_beratung-mueller',
        ]);

        $file = UploadedFile::fake()->create('dokument.pdf', 100, 'application/pdf');

        actingAs($this->user)
            ->post("/api/advices/{$this->advice->uuid}/nextcloud/upload", ['file' => $file])
            ->assertOk()
            ->assertJsonStructure(['fileId', 'path', 'name', 'size', 'mimeType']);
    });

    test('upload requires a file', function () {
        actingAs($this->user)
            ->post("/api/advices/{$this->advice->uuid}/nextcloud/upload", [])
            ->assertUnprocessable();
    });
});

describe('files', function () {
    test('returns empty array when no folder linked', function () {
        actingAs($this->user)
            ->get("/api/advices/{$this->advice->uuid}/nextcloud/files")
            ->assertOk()
            ->assertJson([]);
    });

    test('returns file listing when folder is linked', function () {
        $this->advice->update([
            'nextcloud_folder_id' => '10',
            'nextcloud_folder_path' => '/Beratungen/Offen/2024-01-15_beratung-mueller',
        ]);

        actingAs($this->user)
            ->get("/api/advices/{$this->advice->uuid}/nextcloud/files")
            ->assertOk()
            ->assertJsonIsArray();
    });

    test('returns 422 when linked folder not found', function () {
        $this->advice->update([
            'nextcloud_folder_id' => '99',
            'nextcloud_folder_path' => '/does-not-exist',
        ]);

        actingAs($this->user)
            ->get("/api/advices/{$this->advice->uuid}/nextcloud/files")
            ->assertUnprocessable();
    });
});

describe('download', function () {
    test('can download a file by path', function () {
        $this->advice->update([
            'nextcloud_folder_id' => '10',
            'nextcloud_folder_path' => '/Beratungen/Offen/2024-01-15_beratung-mueller',
        ]);

        $path = '/Beratungen/Offen/2024-01-15_beratung-mueller/dokument.pdf';

        actingAs($this->user)
            ->get("/api/advices/{$this->advice->uuid}/nextcloud/download?path=".urlencode($path))
            ->assertOk();
    });

    test('cannot download file outside linked folder', function () {
        $this->advice->update([
            'nextcloud_folder_id' => '10',
            'nextcloud_folder_path' => '/Beratungen/Offen/2024-01-15_beratung-mueller',
        ]);

        actingAs($this->user)
            ->get("/api/advices/{$this->advice->uuid}/nextcloud/download?path=".urlencode('/Fertig/andere-datei.pdf'))
            ->assertForbidden();
    });

    test('unauthorized user cannot download', function () {
        $otherUser = User::factory()->create();

        actingAs($otherUser)
            ->get("/api/advices/{$this->advice->uuid}/nextcloud/download?path=".urlencode('/Offen/2024-01-15_beratung-mueller/dokument.pdf'))
            ->assertForbidden();
    });
});
