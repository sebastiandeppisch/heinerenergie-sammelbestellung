<?php

use App\Contracts\MailCredentialsRepository;
use App\Data\MailCredentialsData;
use App\Services\UserEncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeEncKey(): string
{
    $service = new UserEncryptionService;
    $salt = base64_encode(random_bytes(32));

    return $service->deriveKey('password', $salt);
}

function bindEncKey(string $key): void
{
    app()->instance('user.enc_key', base64_decode($key));
}

test('store saves encrypted credentials in session', function (): void {
    bindEncKey(makeEncKey());
    $repo = app(MailCredentialsRepository::class);

    $data = new MailCredentialsData(
        imapHost: 'imap.example.com',
        imapPort: 993,
        smtpHost: 'smtp.example.com',
        smtpPort: 587,
        username: 'user@example.com',
        password: 'secret',
    );

    $repo->store($data);

    // Raw session value should be encrypted (not plaintext)
    $raw = session()->get('mail_credentials');
    expect($raw)->not->toBeNull();
    expect($raw)->not->toContain('secret');
    expect($raw)->not->toContain('user@example.com');
});

test('get retrieves and decrypts stored credentials', function (): void {
    bindEncKey(makeEncKey());
    $repo = app(MailCredentialsRepository::class);

    $original = new MailCredentialsData(
        imapHost: 'imap.example.com',
        imapPort: 993,
        smtpHost: 'smtp.example.com',
        smtpPort: 587,
        username: 'user@example.com',
        password: 'secret',
    );

    $repo->store($original);
    $retrieved = $repo->get();

    expect($retrieved)->not->toBeNull();
    expect($retrieved->imapHost)->toBe('imap.example.com');
    expect($retrieved->imapPort)->toBe(993);
    expect($retrieved->smtpHost)->toBe('smtp.example.com');
    expect($retrieved->smtpPort)->toBe(587);
    expect($retrieved->username)->toBe('user@example.com');
    expect($retrieved->password)->toBe('secret');
});

test('get returns null when no credentials stored', function (): void {
    bindEncKey(makeEncKey());
    $repo = app(MailCredentialsRepository::class);

    expect($repo->get())->toBeNull();
});

test('clear removes credentials from session', function (): void {
    bindEncKey(makeEncKey());
    $repo = app(MailCredentialsRepository::class);

    $repo->store(new MailCredentialsData(
        imapHost: 'imap.example.com',
        imapPort: 993,
        smtpHost: 'smtp.example.com',
        smtpPort: 587,
        username: 'user@example.com',
        password: 'secret',
    ));

    $repo->clear();

    expect($repo->get())->toBeNull();
    expect(session()->has('mail_credentials'))->toBeFalse();
});

test('get returns null and clears session on stale credentials (wrong key)', function (): void {
    // Store with one key
    $key1 = makeEncKey();
    bindEncKey($key1);
    $repo = app(MailCredentialsRepository::class);

    $repo->store(new MailCredentialsData(
        imapHost: 'imap.example.com',
        imapPort: 993,
        smtpHost: 'smtp.example.com',
        smtpPort: 587,
        username: 'user@example.com',
        password: 'secret',
    ));

    // Now bind a different key (simulates password change)
    $key2 = makeEncKey();
    bindEncKey($key2);

    $result = $repo->get();

    expect($result)->toBeNull();
    expect(session()->has('mail_credentials'))->toBeFalse();
});
