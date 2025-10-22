<?php
namespace App\Service;


use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkDetails;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class LoginLinkService
{
    public function __construct(
        private readonly LoginLinkHandlerInterface $loginLinkHandler,
    ) {}

    /**
     * Creates and sends a login link notification to the user's email.
     */
    public function sendLoginLink(UserInterface $user, string $subject = 'Your login link'): LoginLinkDetails
    {
        $loginLinkDetails = $this->loginLinkHandler->createLoginLink($user);
        return $loginLinkDetails;
    }
}

