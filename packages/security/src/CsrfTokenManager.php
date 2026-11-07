<?php

declare(strict_types=1);

namespace CnA\Security;

class CsrfTokenManager
{
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function verify(?string $known, ?string $user): bool
    {
        if ($known === null || $user === null || $known === '' || $user === '') {
            return false;
        }

        return hash_equals($known, $user);
    }
}

