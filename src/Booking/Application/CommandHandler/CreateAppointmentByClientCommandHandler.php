<?php

namespace App\Booking\Application\CommandHandler;

use App\Availability\Public\Query\IsWithinProBusinessTimeQueryInterface;
use App\Booking\Application\Command\CreateAppointmentByClientCommand;
use App\Booking\Application\Exception\OutsideProBusinessTimeException;
use App\Booking\Application\Exception\AppointmentOverlapException;
use App\Booking\Application\Repository\Appointment\AppointmentRepositoryInterface;
use App\Booking\Application\Repository\Appointment\OverlapRepositoryInterface;

final class CreateAppointmentByClientCommandHandler
{
    public function __construct(
        private readonly OverlapRepositoryInterface $overlapRepository,
        private readonly AppointmentRepositoryInterface $appointmentRepository,
        private readonly IsWithinProBusinessTimeQueryInterface $isWithinProBusinessTime
    ) {
    }

    public function __invoke(CreateAppointmentByClientCommand $command): void
    {
        if ($command->proId <= 0) {
            throw new \InvalidArgumentException('proId must be a positive integer.');
        }

        if ($command->serviceId <= 0) {
            throw new \InvalidArgumentException('serviceId must be a positive integer.');
        }

        $startDateTimeUtc = $command->startDateTimeUtc->setTimezone(new \DateTimeZone('UTC'));

        if (! $this->overlapRepository->doesServiceBelongsToPro($command->serviceId, $command->proId)) {
            throw new \DomainException('Service does not belong to the pro.');
        }

        if (! $this->isWithinProBusinessTime->isWithinProBusinessTime(
            $command->proId,
            $command->startDateTimeUtc
        )) {
            throw new OutsideProBusinessTimeException();
        }

        if ($this->overlapRepository->existsOverlappingAppointment(
            $command->proId,
            $command->serviceId,
            $startDateTimeUtc
        )) {
            throw new AppointmentOverlapException();
        }

        $this->appointmentRepository->createFromClient(
            $command->proId,
            $command->serviceId,
            $startDateTimeUtc,
            $command->lastName,
            $command->firstName,
            $command->email
        );
    }
}
