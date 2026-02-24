<?php

namespace App\ProfilePro\Public\Exception;

final class ProNotFound extends \DomainException
{
    public function __construct(string $message = 'pro not found.')
    {
        parent::__construct($message);
    }
}
