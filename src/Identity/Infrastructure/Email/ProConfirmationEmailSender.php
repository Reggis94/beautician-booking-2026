<?php

namespace App\Identity\Infrastructure\Email;

use App\Identity\Application\Port\ProConfirmationEmailSenderInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class ProConfirmationEmailSender implements ProConfirmationEmailSenderInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromAddress,
        private readonly string $baseUrl
    ) {
    }

    public function sendConfirmationEmail(string $proEmail, string $confirmationToken): void
    {
        $confirmationUrl = rtrim($this->baseUrl, '/') . '/pro/confirm?token=' . urlencode($confirmationToken);

        $email = (new Email())
            ->from($this->fromAddress)
            ->to($proEmail)
            ->subject('Confirm your email')
            ->text(
                "Please confirm your email by clicking the link:\n" . $confirmationUrl
            );

        $this->mailer->send($email);
    }
}
