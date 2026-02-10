<?php

namespace App\Booking\Application\Command;

final readonly class CreateAppointmentByClientCommand
{
    public function __construct(
        public int $proId,
        public int $serviceId,
        public \DateTimeImmutable $startDateTimeUtc,
        public string $lastName,
        public string $firstName,
        public string $email
    ) {
    }
}
