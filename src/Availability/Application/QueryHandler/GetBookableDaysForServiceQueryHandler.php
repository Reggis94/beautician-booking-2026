<?php

namespace App\Availability\Application\QueryHandler;

use App\Availability\Application\Query\GetBookableDaysForServiceQuery;
use App\Availability\Domain\Repository\AvailabilityRepositoryInterface;

final class GetBookableDaysForServiceQueryHandler
{
    public function __construct(private readonly AvailabilityRepositoryInterface $repository)
    {
    }

    /**
     * @return array<int, string>
     */
    public function __invoke(GetBookableDaysForServiceQuery $query): array
    {
        return $this->repository->getBookableDaysForService(
            $query->serviceId,
            new \DateTimeImmutable($query->yearMonth . '-01')
        );
    }
}
