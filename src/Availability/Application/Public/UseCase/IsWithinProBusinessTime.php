<?php

namespace App\Availability\Application\Public\UseCase;

use App\Availability\Application\Public\UseCase\Contract\IsWithinProBusinessTimeInterface;
use App\Availability\Domain\Repository\AvailabilityRepositoryInterface;

final class IsWithinProBusinessTime implements IsWithinProBusinessTimeInterface
{
    public function __construct(private readonly AvailabilityRepositoryInterface $repository)
    {
    }

    public function isWithinProBusinessTime(int $proId, \DateTimeImmutable $proLocalDateTime): bool
    {
        return $this->repository->isWithinProBusinessTime($proId, $proLocalDateTime);
    }
}
