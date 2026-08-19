<?php

use App\Contracts\MailCredentialsRepository;
use App\Contracts\MailServiceContract;
use App\Data\MailCredentialsData;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use App\Services\UserEncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

function loginWithEncKey(User $user, string $password = 'password123'): string
{
    $service = new UserEncryptionService;
    $salt = base64_encode(random_bytes(32));
    $key = $service->deriveKey($password, $salt);
    app()->instance('user.enc_key', base64_decode($key));

    return $key;
}

beforeEach(function (): void {
    $this->user = User::factory()->create(['password' => bcrypt('password123')]);

    $group = Group::create(['name' => 'Test Group', 'description' => '']);
    app(SessionService::class)->actAsGroup($group);
    Config::set('app.group_context', 'global');
});

test('GET /mail/account is accessible without enc_key cookie', function (): void {
    $this->actingAs($this->user)
        ->get('/mail/account')
        ->assertOk();
});

test('GET /mail/account renders Inertia page with hasAccount false when no credentials stored', function (): void {
    $key = loginWithEncKey($this->user);

    $this->actingAs($this->user)
        ->withCookie('enc_key', $key)
        ->get('/mail/account')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Mail/Account')
            ->where('hasAccount', false)
        );
});

test('GET /mail/account renders Inertia page with hasAccount true when credentials stored', function (): void {
    $key = loginWithEncKey($this->user);

    app(MailCredentialsRepository::class)->store(new MailCredentialsData(
        imapHost: 'imap.example.com',
        imapPort: 993,
        smtpHost: 'smtp.example.com',
        smtpPort: 587,
        username: 'user@example.com',
        password: 'secret',
    ));

    $this->actingAs($this->user)
        ->withCookie('enc_key', $key)
        ->get('/mail/account')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Mail/Account')
            ->where('hasAccount', true)
        );
});

test('POST /mail/account tests connection and stores credentials on success', function (): void {
    $key = loginWithEncKey($this->user);

    $this->mock(MailServiceContract::class, function (MockInterface $mock): void {
        $mock->shouldReceive('testConnection')->once()->andReturn();
    });

    $this->actingAs($this->user)
        ->withCookie('enc_key', $key)
        ->post('/mail/account', [
            'imap_host' => 'imap.example.com',
            'imap_port' => 993,
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'username' => 'user@example.com',
            'password' => 'secret',
        ])
        ->assertRedirect(route('mail.account.show'));

    expect(app(MailCredentialsRepository::class)->get()?->username)->toBe('user@example.com');
});

test('POST /mail/account returns connection error when IMAP fails', function (): void {
    $key = loginWithEncKey($this->user);

    $this->mock(MailServiceContract::class, function (MockInterface $mock): void {
        $mock->shouldReceive('testConnection')->once()->andThrow(new RuntimeException('Connection refused'));
    });

    $this->actingAs($this->user)
        ->withCookie('enc_key', $key)
        ->post('/mail/account', [
            'imap_host' => 'imap.example.com',
            'imap_port' => 993,
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'username' => 'user@example.com',
            'password' => 'wrong',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('connection');

    expect(app(MailCredentialsRepository::class)->get())->toBeNull();
});

test('POST /mail/account validates required fields', function (): void {
    $key = loginWithEncKey($this->user);

    $this->actingAs($this->user)
        ->withCookie('enc_key', $key)
        ->post('/mail/account', [])
        ->assertSessionHasErrors(['imap_host', 'smtp_host', 'username', 'password']);
});

test('DELETE /mail/account clears credentials and redirects', function (): void {
    $key = loginWithEncKey($this->user);

    $repo = app(MailCredentialsRepository::class);
    $repo->store(new MailCredentialsData(
        imapHost: 'imap.example.com',
        imapPort: 993,
        smtpHost: 'smtp.example.com',
        smtpPort: 587,
        username: 'user@example.com',
        password: 'secret',
    ));

    $this->actingAs($this->user)
        ->withCookie('enc_key', $key)
        ->delete('/mail/account')
        ->assertRedirect(route('mail.account.show'));

    expect($repo->get())->toBeNull();
});

test('POST /mail/discover flashes discovered config and redirects', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'v1.ispdb.net/*' => Http::response([
            'imap' => [['hostname' => 'imap.example.com', 'port' => 993, 'socketType' => 'SSL']],
            'smtp' => [['hostname' => 'smtp.example.com', 'port' => 587, 'socketType' => 'STARTTLS']],
        ], 200),
    ]);

    $this->actingAs($this->user)
        ->from(route('mail.account.show'))
        ->post('/mail/discover', ['email' => 'user@example.com'])
        ->assertRedirect(route('mail.account.show'));
});

test('POST /mail/discover flashes discoverFailed when nothing found', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'v1.ispdb.net/*' => Http::response([], 200),
        'autoconfig.example.com/*' => Http::response('', 404),
        'autodiscover.example.com/*' => Http::response('', 404),
    ]);

    $this->actingAs($this->user)
        ->from(route('mail.account.show'))
        ->post('/mail/discover', ['email' => 'user@example.com'])
        ->assertRedirect(route('mail.account.show'));
});
