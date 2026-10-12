<?php

namespace App\ProAuth\Infrastructure\Mailer;

use App\ProAuth\Application\Exception\LoginLinkDeliveryFailedException;
use App\ProAuth\Application\Mailer\LoginLinkMailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Sends the login link email, in French, through Symfony Mailer.
 *
 * The transport is configured by MAILER_DSN (Mailchimp Transactional API: mandrill+api://).
 * No Messenger bus is installed, so MailerInterface::send() delivers synchronously.
 *
 * The token is put in the URL fragment so it is never sent to the frontend server, its access logs or Referer
 * headers. The frontend confirmation page reads it from window.location.hash.
 */
final class SymfonyLoginLinkMailer implements LoginLinkMailerInterface
{
    public function __construct(
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

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            throw new LoginLinkDeliveryFailedException($exception);
        }
    }
}
