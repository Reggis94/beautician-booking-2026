<?php

namespace App\ProAuth\UI\Http\Security;

use App\ProAuth\Application\Query\GetAuthenticatedProQuery;
use App\ProAuth\Application\QueryHandler\GetAuthenticatedProQueryHandler;
use App\ProAuth\Public\AuthenticatedPro;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Authenticates a beautician from the session cookie, validated against the database on every request.
 */
final class SessionAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly SessionCookie $sessionCookie,
        private readonly GetAuthenticatedProQueryHandler $getAuthenticatedProQueryHandler
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $this->sessionCookie->read($request) !== null;
    }

    public function authenticate(Request $request): Passport
    {
        $authenticatedPro = ($this->getAuthenticatedProQueryHandler)(
            new GetAuthenticatedProQuery((string) $this->sessionCookie->read($request))
        );

        if ($authenticatedPro === null) {
            throw new CustomUserMessageAuthenticationException('Invalid or revoked session.');
        }

        return new SelfValidatingPassport(
            new UserBadge(
                $authenticatedPro->getUserIdentifier(),
                function () use ($authenticatedPro): AuthenticatedPro {
                    return $authenticatedPro;
                }
            )
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    /**
     * Continues unauthenticated: public endpoints keep working with a stale cookie, and access control
     * rejects protected endpoints through start().
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new Response('', Response::HTTP_UNAUTHORIZED);
    }
}
