<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class UserEncryptionService
{
    private const string CIPHER = 'aes-256-gcm';

    private const int ITERATIONS = 100_000;

    private const int KEY_LENGTH = 32;

    private const int NONCE_LENGTH = 12;

    private const int TAG_LENGTH = 16;

    /** @return string base64-encoded 32-byte key */
    public function deriveKey(string $password, string $salt): string
    {
        $key = hash_pbkdf2('sha256', $password, base64_decode($salt), self::ITERATIONS, self::KEY_LENGTH, true);

        return base64_encode($key);
    }

    /** @return string format: base64(nonce):base64(ciphertext):base64(tag) */
    public function encrypt(string $plaintext, string $key): string
    {
        $nonce = random_bytes(self::NONCE_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            base64_decode($key),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            self::TAG_LENGTH,
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Encryption failed');
        }

        return base64_encode($nonce).':'.base64_encode($ciphertext).':'.base64_encode($tag);
    }

    public function decrypt(string $payload, string $key): string
    {
        $parts = explode(':', $payload);

        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid encrypted payload format');
        }

        [$nonce, $ciphertext, $tag] = array_map(base64_decode(...), $parts);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            base64_decode($key),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
        );

        if ($plaintext === false) {
            throw new RuntimeException('Decryption failed: authentication tag mismatch');
        }

        return $plaintext;
    }
}
