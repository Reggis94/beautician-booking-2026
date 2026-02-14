<?php

namespace App\Identity\Application\Port;

interface ProConfirmationEmailSenderInterface
{
    public function sendConfirmationEmail(string $proEmail, string $confirmationToken): void;
}
