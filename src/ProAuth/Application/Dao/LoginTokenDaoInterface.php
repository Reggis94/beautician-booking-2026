<?php

namespace App\ProAuth\Application\Dao;

interface LoginTokenDaoInterface
{
    /**
     * Returns the non-deleted beautician (pro) ID for a lowercase email address.
     */
    public function findActiveProIdByEmail(string $email): ?int;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;

    /**
     * Locks the beautician row until the end of the transaction, so concurrent login link requests of the same
     * beautician are handled one after the other.
     */
    public function lockPro(int $proId): void;

    /**
     * Whether a login token was created for the beautician during the last given number of seconds.
     */
    public function existsCreatedWithinSeconds(int $proId, int $seconds): bool;

    /**
     * Number of login tokens created for the beautician during the last given number of seconds.
     */
    public function countCreatedWithinSeconds(int $proId, int $seconds): int;

    public function create(int $proId, string $tokenHash): void;

    /**
     * Makes an unconsumed token unusable because its email was not delivered. The row is kept so the attempt
     * still counts in the cooldown and the rate limit.
     */
    public function markDeliveryFailed(string $tokenHash): void;

    /**
     * Atomically marks an unconsumed, delivered token of a non-deleted beautician as consumed and returns its
     * pro ID. Returns null when the token is unknown, already consumed (including by a concurrent request) or
     * its delivery failed.
     */
    public function consume(string $tokenHash): ?int;
}
