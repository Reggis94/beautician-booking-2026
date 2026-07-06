<?php

namespace App\Booking\Application\CommandHandler;

use App\Availability\Public\Query\ServiceIsWithinProBusinessTimeQueryInterface;
use App\Booking\Application\Command\CreateAppointmentByClientCommand;
use App\Booking\Application\Exception\AppointmentOverlapException;
use App\Booking\Application\Exception\AppointmentStartDateTimeInPastException;
use App\Booking\Application\Exception\OutsideProBusinessTimeException;
use App\Booking\Application\Exception\ServiceDoesNotBelongToProException;
use App\Booking\Application\Repository\Appointment\AppointmentRepositoryInterface;
use App\Booking\Application\Repository\Appointment\OverlapRepositoryInterface;
use App\ProfilePro\Public\Query\ProTimezoneQueryInterface;

final class CreateAppointmentByClientCommandHandler
{
    public function __construct(
        private readonly OverlapRepositoryInterface $overlapRepository,
        private readonly AppointmentRepositoryInterface $appointmentRepository,
        private readonly ServiceIsWithinProBusinessTimeQueryInterface $isWithinProBusinessTime,
        private readonly ProTimezoneQueryInterface $getProTimezone
    ) {
    }

    public function __invoke(CreateAppointmentByClientCommand $command): void
    {
        // See docs/future-improvements/booking/book-0002-derive-pro-id-from-service-for-client-appointment.md.
        if (! $this->overlapRepository->doesServiceBelongsToPro($command->serviceId, $command->proId)) {
            throw new ServiceDoesNotBelongToProException();
        }

        $proDateTimeWithLocalTz = new \DateTimeImmutable(
            $command->startDateTimeLocal,
            new \DateTimeZone($this->getProTimezone->getProTimezone($command->proId))
        );
        // Convert the pro-local request datetime to the UTC appointment storage instant.
        // See docs/adr/0013-datetime-timezone-policy.md.
        $startDateTimeUtc = $proDateTimeWithLocalTz->setTimezone(new \DateTimeZone('UTC'));

        if (new \DateTime('now', new \DateTimeZone('UTC')) >= $startDateTimeUtc) {
            throw new AppointmentStartDateTimeInPastException();
        }

        $this->appointmentRepository->beginTransaction();

        try {
            $this->appointmentRepository->lockProAppointments($command->proId);

            if (! $this->isWithinProBusinessTime->isServiceWithinProBusinessTime(
                $command->proId,
                $command->serviceId,
                $command->startDateTimeLocal
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
                $command->email,
                $command->extraPhone
            );

            $this->appointmentRepository->commit();
        } catch (\Throwable $exception) {
            $this->appointmentRepository->rollBack();
            throw $exception;
        }
    }
}
