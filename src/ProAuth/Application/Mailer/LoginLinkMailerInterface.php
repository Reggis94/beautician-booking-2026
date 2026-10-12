<?php

namespace App\ProAuth\Application\Mailer;

use App\ProAuth\Application\Exception\LoginLinkDeliveryFailedException;

interface LoginLinkMailerInterface
{
    /**
     * Sends, synchronously, the login link pointing to the frontend confirmation page.
     *
     * @throws LoginLinkDeliveryFailedException
     */
    public function sendLoginLink(string $proEmail, string $loginToken): void;
}
