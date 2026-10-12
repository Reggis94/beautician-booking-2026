<?php

namespace App\ProAuth\Application\Dao;

interface SessionDaoInterface
{
    public function create(int $proId, string $sessionHash): void;

    public function revoke(string $sessionHash): void;

    /**
     * Returns the beautician owning a non-revoked session, when the beautician is not deleted.
     *
     * @return array{
     *     id: int,
     *     email: string,
     *     link_slug: string,
     *     display_name: ?string,
     *     firstname: ?string,
     *     lastname: ?string
     * }|null
     */
    public function findActiveProBySessionHash(string $sessionHash): ?array;
}
