<?php

namespace App\ProAuth\Infrastructure\Mailer;

use App\ProAuth\Application\Mailer\LoginLinkMailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Sends the login link email, in French, through Symfony Mailer.
 *
 * The transport is configured by MAILER_DSN (Mailchimp Transactional API: mandrill+api://).
 * No Messenger bus is installed, so MailerInterface::send() delivers synchronously.
 *
 * The mailer is injected lazily: Symfony builds the transports from MAILER_DSN when the mailer is instantiated, so
 * an invalid DSN (e.g. IncompleteDsnException) would otherwise be thrown while the controller arguments are
 * resolved, outside of any delivery error handling. Lazily, it is thrown on the first send(), where the caller
 * handles it as a delivery failure.
 *
 * The token is put in the URL fragment so it is never sent to the frontend server, its access logs or Referer
 * headers. The frontend confirmation page reads it from window.location.hash.
 */
final class SymfonyLoginLinkMailer implements LoginLinkMailerInterface
{
    public function __construct(
        #[Autowire(lazy: true)]
        private readonly MailerInterface $mailer,
        #[Autowire('%pro_auth.login_confirm_url%')]
        private readonly string $loginConfirmUrl,
        #[Autowire('%pro_auth.mailer_from_email%')]
        private readonly string $fromEmail,
        #[Autowire('%pro_auth.mailer_from_name%')]
        private readonly string $fromName
    ) {
    }

    public function sendLoginLink(string $proEmail, string $loginToken): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($proEmail)
            ->subject('Votre lien de connexion')
            ->htmlTemplate('pro_auth/email/login_link.html.twig')
            ->context(['login_url' => $this->loginConfirmUrl . '#token=' . $loginToken]);

        // Mailer exceptions (transport failure, incomplete or unsupported DSN, ...) are let through as they are.
        $this->mailer->send($email);
    }
}
