<?php

namespace App\Availability\Application\Exception;

final class PastMonthAvailabilityRequestException extends \DomainException
{
    public function __construct(string $message = 'The month must be the current month or a future month.')
    {
        parent::__construct($message);
    }
}
