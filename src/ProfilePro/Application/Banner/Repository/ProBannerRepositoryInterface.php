<?php

namespace App\ProfilePro\Application\Banner\Repository;

interface ProBannerRepositoryInterface
{
    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;

    public function assertProExists(int $proId): void;

    public function assertProExistsForUpdate(int $proId): void;

    public function countActiveForPro(int $proId): int;

    public function create(int $proId, int $orderNumber, string $fileKey, string $commitId): int;
}
