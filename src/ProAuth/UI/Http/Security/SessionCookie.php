<?php

namespace App\ProAuth\UI\Http\Security;

use App\ProAuth\Application\Token\OpaqueToken;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The HTTP-only cookie carrying the beautician session identifier. The identifier is never returned in a body.
 */
final class SessionCookie
{
    private const PATH = '/';

    public function __construct(
        #[Autowire('%pro_auth.session_cookie_name%')]
        private readonly string $name,
        #[Autowire('%pro_auth.session_cookie_secure%')]
        private readonly bool $secure,
        #[Autowire('%pro_auth.session_cookie_samesite%')]
        private readonly string $sameSite,
        #[Autowire('%pro_auth.session_cookie_max_age_seconds%')]
        private readonly int $maxAgeSeconds
    ) {
    }

    /**
     * Returns the session identifier sent by the browser, or null when it is missing or malformed.
     */
    public function read(Request $request): ?string
    {
        $sessionId = $request->cookies->get($this->name);

        if (!is_string($sessionId) || !OpaqueToken::isWellFormed($sessionId)) {
            return null;
        }

        return $sessionId;
    }

    public function set(Response $response, string $sessionId): void
    {
        // Sessions do not expire server-side; the max age only keeps the cookie across browser restarts.
        $response->headers->setCookie(
            Cookie::create($this->name)
                ->withValue($sessionId)
                ->withExpires(time() + $this->maxAgeSeconds)
                ->withPath(self::PATH)
                ->withSecure($this->secure)
                ->withHttpOnly(true)
                ->withSameSite($this->sameSite)
        );
    }

    public function clear(Response $response): void
    {
        $response->headers->clearCookie($this->name, self::PATH, null, $this->secure, true, $this->sameSite);
    }
}
