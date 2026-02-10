<?php

namespace App\Booking\Application\Exception;

final class OutsideProBusinessTimeException extends \DomainException
{
    public function __construct(string $message = 'Appointment is outside of pro opening hours.')
    {
        parent::__construct($message);
    }
}
