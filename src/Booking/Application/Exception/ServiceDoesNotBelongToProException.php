<?php

namespace App\Booking\Application\Exception;

final class ServiceDoesNotBelongToProException extends \DomainException
{
    public function __construct(string $message = 'Service does not belong to the pro.')
    {
        parent::__construct($message);
    }
}
