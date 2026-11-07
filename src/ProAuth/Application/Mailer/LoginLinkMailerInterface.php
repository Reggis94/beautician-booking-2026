<?php

namespace App\ProAuth\Application\Mailer;

interface LoginLinkMailerInterface
{
    /**
     * Sends, synchronously, the login link pointing to the frontend confirmation page.
     *
     * Any exception means the link was not delivered. Implementations let the original exception through, unwrapped,
     * so the caller can log exactly what failed; the caller handles every exception the same way.
     *
     * @throws \Throwable
     */
    public function sendLoginLink(string $proEmail, string $loginToken): void;
}
