<?php

namespace App\ProAuth\Application\Exception;

final class InvalidLoginTokenException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('The login token is invalid or already used.');
    }
}
