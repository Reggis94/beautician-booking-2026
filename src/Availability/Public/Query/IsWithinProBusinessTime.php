<?php

namespace App\Availability\Public\Query;

use App\Availability\Domain\Repository\AvailabilityRepositoryInterface;

final class IsWithinProBusinessTime implements IsWithinProBusinessTimeQueryInterface
{
    public function __construct(private readonly AvailabilityRepositoryInterface $repository)
    {
    }

    public function isWithinProBusinessTime(int $proId, \DateTimeImmutable $proLocalDateTime): bool
    {
        return $this->repository->isWithinProBusinessTime($proId, $proLocalDateTime);
    }
}
