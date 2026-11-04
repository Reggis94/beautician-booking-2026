<?php

namespace App\ProAuth\UI\Http\EventSubscriber;

use App\ProAuth\UI\Http\Security\SessionCookie;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Browser access rules for the API.
 *
 * The API is only called from the same origin: no CORS headers are sent, so browsers block cross-origin calls.
 *
 * CSRF: state-changing requests that carry the session cookie, and every state-changing login endpoint request,
 * must come from the application itself, checked with the Origin header (or the Referer header when Origin is
 * missing). The SameSite session cookie is a second layer.
 */
final class FrontendOriginSubscriber implements EventSubscriberInterface
{
    private const API_PATH_PREFIX = '/api/';
    private const AUTH_PATH_PREFIX = '/api/auth/';
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(private readonly SessionCookie $sessionCookie)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Before the router (32) and the firewall (8).
            KernelEvents::REQUEST => ['onKernelRequest', 64],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !str_starts_with($request->getPathInfo(), self::API_PATH_PREFIX)) {
            return;
        }

        if ($this->requiresSameOrigin($request) && !$this->isSameOrigin($request)) {
            $event->setResponse(new Response('', Response::HTTP_FORBIDDEN));
        }
    }

    private function requiresSameOrigin(Request $request): bool
    {
        if (in_array($request->getMethod(), self::SAFE_METHODS, true)) {
            return false;
        }

        return str_starts_with($request->getPathInfo(), self::AUTH_PATH_PREFIX)
            || $this->sessionCookie->read($request) !== null;
    }

    private function isSameOrigin(Request $request): bool
    {
        $origin = $request->headers->get('Origin') ?? $this->originOf($request->headers->get('Referer'));

        return $origin !== null && $origin === $request->getSchemeAndHttpHost();
    }

    private function originOf(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $origin = $parts['scheme'] . '://' . $parts['host'];

        return isset($parts['port']) ? $origin . ':' . $parts['port'] : $origin;
    }
}
