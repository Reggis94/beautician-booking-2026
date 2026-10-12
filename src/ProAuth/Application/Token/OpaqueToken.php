<?php

namespace App\ProAuth\Application\Token;

/**
 * Cryptographically secure, unpredictable secrets used for email login tokens and session identifiers.
 *
 * Only the SHA-256 hash of a secret is stored. Login tokens and session identifiers are generated independently,
 * so a session identifier is never derived from a login token.
 */
final class OpaqueToken
{
    public const PATTERN = '/^[0-9a-f]{64}$/';
    private const RANDOM_BYTES = 32;

    public static function generate(): string
    {
        return bin2hex(random_bytes(self::RANDOM_BYTES));
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function isWellFormed(string $token): bool
    {
        return preg_match(self::PATTERN, $token) === 1;
    }
}
