<?php

namespace App\Availability\Application\QueryHandler;

use App\Availability\Application\Exception\PastMonthAvailabilityRequestException;
use App\Availability\Application\Query\GetAvailabilitiesForMonthQuery;
use App\Availability\Domain\Repository\AvailabilityRepositoryInterface;

final class GetAvailabilitiesForMonthQueryHandler
{
    public function __construct(private readonly AvailabilityRepositoryInterface $repository)
    {
    }

    /**
     * @return array<string, array{startTimeLocal: string, endTimeLocal: string}>
     */
    public function __invoke(GetAvailabilitiesForMonthQuery $query): array
    {
        $requestedMonth = new \DateTimeImmutable($query->month . '-01');
        $currentMonth = new \DateTimeImmutable('first day of this month 00:00:00');

        if ($requestedMonth < $currentMonth) {
            throw new PastMonthAvailabilityRequestException();
        }

        $availabilities = $this->repository->getAvailabilitiesForMonth(
            $query->proId,
            $requestedMonth
        );

        $response = [];
        foreach ($availabilities as $availability) {
            $response[$availability['date']] = [
                'startTimeLocal' => $availability['startTimeLocal'],
                'endTimeLocal' => $availability['endTimeLocal'],
            ];
        }

        return $response;
    }
}
