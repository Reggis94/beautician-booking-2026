<?php

namespace App\Booking\Application\Exception;

final class AppointmentOverlapException extends \DomainException
{
    public function __construct(string $message = 'Appointment overlaps an existing booking.')
    {
        parent::__construct($message);
    }
}
