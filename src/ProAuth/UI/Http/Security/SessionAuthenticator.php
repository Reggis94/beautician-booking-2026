<?php

namespace App\ProAuth\UI\Http\Security;

use App\ProAuth\Application\Query\GetAuthenticatedProQuery;
use App\ProAuth\Application\QueryHandler\GetAuthenticatedProQueryHandler;
use App\ProAuth\Public\AuthenticatedPro;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
    private const API_PATH_PREFIX = '/api/';
    private const LOGIN_PAGE_ROUTE = 'pro_auth_login_page_fr';

    public function __construct(
        private readonly SessionCookie $sessionCookie,
        private readonly GetAuthenticatedProQueryHandler $getAuthenticatedProQueryHandler,
        private readonly UrlGeneratorInterface $urlGenerator
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

    /**
     * API requests get an empty 401. Protected pages (/pro/..., /fr/pro/...) redirect a signed-out visitor to the login page.
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        if (!str_starts_with($request->getPathInfo(), self::API_PATH_PREFIX)) {
            return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_PAGE_ROUTE));
        }

        return new Response('', Response::HTTP_UNAUTHORIZED);
    }
}
