<?php

namespace App\Tests\Booking\UI\Http\Controller\Appointment;

use App\Availability\Public\Query\ServiceIsWithinProBusinessTimeQueryInterface;
use App\Booking\Application\Command\CreateAppointmentByClientCommand;
use App\Booking\Application\CommandHandler\CreateAppointmentByClientCommandHandler;
use App\Booking\Application\Repository\Appointment\AppointmentRepositoryInterface;
use App\Booking\Application\Repository\Appointment\OverlapRepositoryInterface;
use App\Booking\UI\Http\Controller\Appointment\CreateAppointmentByClientController;
use App\ProfilePro\Public\Query\ProTimezoneQueryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class CreateAppointmentByClientControllerTest extends TestCase
{
    #[Test]
    public function it_returns_bad_request_when_service_does_not_belong_to_pro(): void
    {
        $controller = new CreateAppointmentByClientController();
        $handler = new CreateAppointmentByClientCommandHandler(
            new ServiceDoesNotBelongToProOverlapRepository(),
            new TransactionTrackingAppointmentRepository(),
            new AlwaysWithinBusinessTimeQuery(),
            new ParisProTimezoneQuery()
        );
        $command = new CreateAppointmentByClientCommand(
            42,
            123,
            '2027-01-15 10:00:00',
            'Doe',
            'Jane',
            'jane.doe@example.com',
            '+15551234567'
        );

        $response = $controller($handler, $command);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Service does not belong to the pro.'],
            json_decode((string) $response->getContent(), true)
        );
    }
}

final class ServiceDoesNotBelongToProOverlapRepository implements OverlapRepositoryInterface
{
    public function doesServiceBelongsToPro(int $serviceId, int $proId): bool
    {
        return false;
    }

    public function existsOverlappingAppointment(
        int $proId,
        int $serviceId,
        \DateTimeImmutable $datetimeStart
    ): bool {
        throw new \LogicException('Overlap check should not run for an invalid service/pro pair.');
    }
}

final class TransactionTrackingAppointmentRepository implements AppointmentRepositoryInterface
{
    public function beginTransaction(): void
    {
        throw new \LogicException('Transaction should not start for an invalid service/pro pair.');
    }

    public function commit(): void
    {
        throw new \LogicException('Transaction should not commit for an invalid service/pro pair.');
    }

    public function rollBack(): void
    {
        throw new \LogicException('Transaction should not roll back for an invalid service/pro pair.');
    }

    public function lockProAppointments(int $proId): void
    {
        throw new \LogicException('Appointments should not be locked for an invalid service/pro pair.');
    }

    /**
     * @return list<array{
     *     id: int,
     *     client_name: string,
     *     service_name: ?string,
     *     start_at: string,
     *     duration_minutes: ?int
     * }>
     */
    public function listProAdminUpcomingAppointments(int $proId): array
    {
        throw new \LogicException('Upcoming appointments should not be listed for an invalid service/pro pair.');
    }

    /**
     * @return list<array{
     *     id: int,
     *     client_name: string,
     *     service_name: ?string,
     *     start_at: string,
     *     duration_minutes: ?int
     * }>
     */
    public function listProAdminPastAppointments(int $proId): array
    {
        throw new \LogicException('Past appointments should not be listed for an invalid service/pro pair.');
    }

    public function createFromClient(
        int $proId,
        int $serviceId,
        \DateTimeImmutable $startDateTimeUtc,
        string $lastName,
        string $firstName,
        string $email,
        string $extraPhone
    ): void {
        throw new \LogicException('Appointment should not be created for an invalid service/pro pair.');
    }
}

final class AlwaysWithinBusinessTimeQuery implements ServiceIsWithinProBusinessTimeQueryInterface
{
    public function isServiceWithinProBusinessTime(
        int $proId,
        int $serviceId,
        string $proLocalDateTime
    ): bool {
        return true;
    }
}

final class ParisProTimezoneQuery implements ProTimezoneQueryInterface
{
    public function getProTimezone(int $proId): string
    {
        return 'Europe/Paris';
    }
}
