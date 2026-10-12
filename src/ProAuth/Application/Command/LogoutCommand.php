<?php

namespace App\ProAuth\Application\Command;

final class LogoutCommand
{
    public function __construct(public readonly ?string $sessionId)
    {
    }
}
