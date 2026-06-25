<?php

namespace App\Booking\Application\Exception;

final class AppointmentStartDateTimeInPastException extends \DomainException
{
    public function __construct(string $message = 'Appointment start datetime must be in the future.')
    {
        parent::__construct($message);
    }
}
