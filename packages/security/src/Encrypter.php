<?php

declare(strict_types=1);

namespace CnA\Security;

use CnA\Contracts\Exceptions\SecurityException;

class Encrypter
{
    protected string $cipher = 'aes-256-gcm';

    public function __construct(
        protected string $key,
        protected int $keyVersion = 1
    ) {
        if (strlen($key) < 32) {
            throw new SecurityException('Encryption key must be at least 32 bytes.');
        }
    }

    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    public function encrypt(mixed $value): string
    {
        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
        $serialized = serialize($value);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $serialized,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($ciphertext === false) {
            throw new SecurityException('Could not encrypt the data.');
        }

        $payload = [
            'v' => $this->keyVersion,
            'iv' => base64_encode($iv),
            'value' => base64_encode($ciphertext),
            'tag' => base64_encode($tag),
        ];

        return base64_encode(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function decrypt(string $payload): mixed
    {
        $decodedJson = base64_decode($payload, true);
        if ($decodedJson === false) {
            throw new SecurityException('Payload is not valid base64.');
        }

        try {
            $data = json_decode($decodedJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new SecurityException('Payload is not valid JSON.', 0, $e);
        }

        if (!isset($data['iv'], $data['value'], $data['tag'])) {
            throw new SecurityException('Invalid payload format.');
        }

        $iv = base64_decode($data['iv'], true);
        $ciphertext = base64_decode($data['value'], true);
        $tag = base64_decode($data['tag'], true);

        $decrypted = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new SecurityException('Could not decrypt data: AEAD tag authentication failed.');
        }

        return unserialize($decrypted);
    }
}

