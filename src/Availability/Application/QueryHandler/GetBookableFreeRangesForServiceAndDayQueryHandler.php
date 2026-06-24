<?php

namespace App\Availability\Application\QueryHandler;

use App\Availability\Application\Query\GetBookableFreeRangesForServiceAndDayQuery;
use App\Availability\Domain\Repository\AvailabilityRepositoryInterface;

final class GetBookableFreeRangesForServiceAndDayQueryHandler
{
    public function __construct(private readonly AvailabilityRepositoryInterface $repository)
    {
    }

    /**
     * @return array<int, array{date: string, start_time: string, end_time: string, duration_min: int}>
     */
    public function __invoke(GetBookableFreeRangesForServiceAndDayQuery $query): array
    {
        return $this->repository->getBookableFreeRangesForServiceAndDay(
            $query->serviceId,
            new \DateTimeImmutable($query->localDate)
        );
    }
}
