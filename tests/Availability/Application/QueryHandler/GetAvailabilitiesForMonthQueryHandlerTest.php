<?php

namespace App\Tests\Availability\Application\QueryHandler;

use App\Availability\Application\Exception\PastMonthAvailabilityRequestException;
use App\Availability\Application\Query\GetAvailabilitiesForMonthQuery;
use App\Availability\Application\QueryHandler\GetAvailabilitiesForMonthQueryHandler;
use App\Availability\Domain\Entity\AvailabilityEntity;
use App\Availability\Domain\Repository\AvailabilityRepositoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GetAvailabilitiesForMonthQueryHandlerTest extends TestCase
{
    #[Test]
    public function it_returns_availabilities_indexed_by_local_date(): void
    {
        $repository = new FixedMonthAvailabilityRepository();
        $handler = new GetAvailabilitiesForMonthQueryHandler($repository);

        $response = $handler(new GetAvailabilitiesForMonthQuery(42, '2099-06'));

        self::assertSame(42, $repository->proId);
        self::assertSame('2099-06-01', $repository->monthStart?->format('Y-m-d'));
        self::assertSame(
            [
                '2099-06-28' => [
                    'startTimeLocal' => '09:00',
                    'endTimeLocal' => '17:00',
                ],
                '2099-06-30' => [
                    'startTimeLocal' => '09:00',
                    'endTimeLocal' => '12:00',
                ],
            ],
            $response
        );
    }

    #[Test]
    public function it_rejects_a_past_month(): void
    {
        $handler = new GetAvailabilitiesForMonthQueryHandler(new FixedMonthAvailabilityRepository());

        $this->expectException(PastMonthAvailabilityRequestException::class);
        $this->expectExceptionMessage('The month must be the current month or a future month.');

        $handler(new GetAvailabilitiesForMonthQuery(42, '2000-01'));
    }
}

final class FixedMonthAvailabilityRepository implements AvailabilityRepositoryInterface
{
    public ?int $proId = null;

    public ?\DateTimeImmutable $monthStart = null;

    public function beginTransaction(): void
    {
    }

    public function commit(): void
    {
    }

    public function rollBack(): void
    {
    }

    public function create(AvailabilityEntity $availability): int
    {
        throw new \LogicException('Create should not run for availability reads.');
    }

    public function findOverlaps(
        int $proId,
        \DateTimeImmutable $weekStartDate,
        \DateTimeImmutable $weekEndDate
    ): array {
        throw new \LogicException('Overlap lookup should not run for availability reads.');
    }

    public function deleteOverlaps(
        int $proId,
        \DateTimeImmutable $weekStartDate,
        \DateTimeImmutable $weekEndDate
    ): void {
        throw new \LogicException('Overlap delete should not run for availability reads.');
    }

    public function getAvailabilitiesForMonth(int $proId, \DateTimeImmutable $monthStart): array
    {
        $this->proId = $proId;
        $this->monthStart = $monthStart;

        return [
            [
                'date' => '2099-06-28',
                'startTimeLocal' => '09:00',
                'endTimeLocal' => '17:00',
            ],
            [
                'date' => '2099-06-30',
                'startTimeLocal' => '09:00',
                'endTimeLocal' => '12:00',
            ],
        ];
    }

    public function getBookableDaysForService(int $serviceId, \DateTimeImmutable $monthStart): array
    {
        throw new \LogicException('Bookable days should not run for availability reads.');
    }

    public function getBookableFreeRangesForServiceAndDay(
        int $serviceId,
        \DateTimeImmutable $localDate
    ): array {
        throw new \LogicException('Bookable ranges should not run for availability reads.');
    }

    public function isServiceWithinProBusinessTime(
        int $proId,
        int $serviceId,
        string $proLocalDateTime
    ): bool {
        throw new \LogicException('Business time check should not run for availability reads.');
    }
}
