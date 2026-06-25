<?php

namespace App\Availability\Public\Query;

use App\Availability\Domain\Repository\AvailabilityRepositoryInterface;

final class ServiceIsWithinProBusinessTime implements ServiceIsWithinProBusinessTimeQueryInterface
{
    public function __construct(private readonly AvailabilityRepositoryInterface $repository)
    {
    }

    public function isServiceWithinProBusinessTime(
        int $proId,
        int $serviceId,
        string $proLocalDateTime
    ): bool {
        return $this->repository->isServiceWithinProBusinessTime(
            $proId,
            $serviceId,
            $proLocalDateTime
        );
    }
}
