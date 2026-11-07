<?php

declare(strict_types=1);

namespace Tests\Unit;

use CnA\Contracts\Exceptions\SecurityException;
use CnA\Security\CsrfTokenManager;
use CnA\Security\Encrypter;
use CnA\Security\HtmlEscaper;
use CnA\Security\SsrfValidator;
use CnA\Testing\TestCase;

class SecurityTest extends TestCase
{
    public function testAeadEncryptionAndDecryption(): void
    {
        $rawKey = random_bytes(32);
        $encrypter = new Encrypter($rawKey);

        $secretPayload = ['user_id' => 42, 'role' => 'admin'];
        $ciphertext = $encrypter->encrypt($secretPayload);

        $this->assertTrue(is_string($ciphertext));
        $this->assertTrue($ciphertext !== serialize($secretPayload));

        $decrypted = $encrypter->decrypt($ciphertext);
        $this->assertEquals($secretPayload, $decrypted);
    }

    public function testAeadRejectsTamperedPayload(): void
    {
        $rawKey = random_bytes(32);
        $encrypter = new Encrypter($rawKey);

        $ciphertext = $encrypter->encrypt('Confidential Data');

        // Tamper with payload
        $rawJson = base64_decode($ciphertext);
        $decoded = json_decode($rawJson, true);
        $decoded['value'] = base64_encode('Tampered Data');
        $tamperedCiphertext = base64_encode(json_encode($decoded));

        $caught = false;
        try {
            $encrypter->decrypt($tamperedCiphertext);
        } catch (SecurityException $e) {
            $caught = true;
            $this->assertTrue(str_contains($e->getMessage(), 'AEAD tag authentication failed'));
        }

        $this->assertTrue($caught, 'AEAD encryption must fail when ciphertext is tampered');
    }

    public function testConstantTimeCsrfVerification(): void
    {
        $token = CsrfTokenManager::generateToken();
        $this->assertTrue(CsrfTokenManager::verify($token, $token));
        $this->assertFalse(CsrfTokenManager::verify($token, 'invalid-token'));
        $this->assertFalse(CsrfTokenManager::verify(null, $token));
    }

    public function testSsrfDefenseBlocksPrivateAndLoopbackIps(): void
    {
        $ssrf = new SsrfValidator();

        $this->assertTrue($ssrf->isPrivateOrReservedIp('127.0.0.1'));
        $this->assertTrue($ssrf->isPrivateOrReservedIp('10.0.0.1'));
        $this->assertTrue($ssrf->isPrivateOrReservedIp('192.168.1.1'));
        $this->assertTrue($ssrf->isPrivateOrReservedIp('169.254.169.254')); // AWS metadata IP
        $this->assertFalse($ssrf->isPrivateOrReservedIp('8.8.8.8'));
    }

    public function testHtmlEscaper(): void
    {
        $malicious = "<script>alert('xss & \"attack\"')</script>";
        $escaped = HtmlEscaper::escape($malicious);

        $this->assertFalse(str_contains($escaped, '<script>'));
        $this->assertTrue(str_contains($escaped, '&lt;script&gt;'));
        $this->assertTrue(str_contains($escaped, '&quot;attack&quot;'));
    }
}

