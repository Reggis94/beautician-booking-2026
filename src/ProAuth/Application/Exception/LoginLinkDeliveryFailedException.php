<?php

namespace App\ProAuth\Application\Exception;

final class LoginLinkDeliveryFailedException extends \RuntimeException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('The login link email could not be delivered.', 0, $previous);
    }
}
