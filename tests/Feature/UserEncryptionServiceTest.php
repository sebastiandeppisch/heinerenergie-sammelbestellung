<?php

use App\Services\UserEncryptionService;

test('encrypt and decrypt round-trip returns original plaintext', function (): void {
    $service = new UserEncryptionService;
    $key = $service->deriveKey('mysecretpassword', base64_encode(random_bytes(32)));

    $plaintext = 'my-imap-password-123!';
    $encrypted = $service->encrypt($plaintext, $key);
    $decrypted = $service->decrypt($encrypted, $key);

    expect($decrypted)->toBe($plaintext);
});

test('same password and salt always derives the same key', function (): void {
    $service = new UserEncryptionService;
    $salt = base64_encode(random_bytes(32));

    $key1 = $service->deriveKey('password', $salt);
    $key2 = $service->deriveKey('password', $salt);

    expect($key1)->toBe($key2);
});

test('different passwords derive different keys', function (): void {
    $service = new UserEncryptionService;
    $salt = base64_encode(random_bytes(32));

    $key1 = $service->deriveKey('password1', $salt);
    $key2 = $service->deriveKey('password2', $salt);

    expect($key1)->not->toBe($key2);
});

test('encrypt produces different ciphertext each time due to random nonce', function (): void {
    $service = new UserEncryptionService;
    $key = $service->deriveKey('password', base64_encode(random_bytes(32)));

    $encrypted1 = $service->encrypt('same plaintext', $key);
    $encrypted2 = $service->encrypt('same plaintext', $key);

    expect($encrypted1)->not->toBe($encrypted2);
});

test('decrypt throws on tampered ciphertext', function (): void {
    $service = new UserEncryptionService;
    $key = $service->deriveKey('password', base64_encode(random_bytes(32)));
    $encrypted = $service->encrypt('secret', $key);

    $parts = explode(':', $encrypted);
    $parts[1] = base64_encode('tampered');
    $tampered = implode(':', $parts);

    expect(fn (): string => $service->decrypt($tampered, $key))->toThrow(RuntimeException::class);
});

test('decrypt throws on tampered auth tag', function (): void {
    $service = new UserEncryptionService;
    $key = $service->deriveKey('password', base64_encode(random_bytes(32)));
    $encrypted = $service->encrypt('secret', $key);

    $parts = explode(':', $encrypted);
    $parts[2] = base64_encode(random_bytes(16));
    $tampered = implode(':', $parts);

    expect(fn (): string => $service->decrypt($tampered, $key))->toThrow(RuntimeException::class);
});

test('derived key is 32 bytes', function (): void {
    $service = new UserEncryptionService;
    $key = $service->deriveKey('password', base64_encode(random_bytes(32)));

    expect(strlen(base64_decode($key)))->toBe(32);
});
