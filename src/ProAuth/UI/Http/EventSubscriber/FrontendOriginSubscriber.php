<?php

namespace App\ProAuth\UI\Http\EventSubscriber;

use App\ProAuth\UI\Http\Security\SessionCookie;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Browser access rules for the API.
 *
 * CORS: only origins listed in AUTH_ALLOWED_ORIGINS may call the API cross-origin, with credentials (cookies).
 *
 * CSRF: state-changing requests that carry the session cookie, and every state-changing login endpoint request,
 * must come from the application itself or from an allowed origin, checked with the Origin header (or the
 * Referer header when Origin is missing). The SameSite session cookie is a second layer.
 */
final class FrontendOriginSubscriber implements EventSubscriberInterface
{
    private const API_PATH_PREFIX = '/api/';
    private const AUTH_PATH_PREFIX = '/api/auth/';
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];
    private const ALLOWED_METHODS = 'GET, POST, PUT, DELETE';
    private const ALLOWED_HEADERS = 'Content-Type';
    private const PREFLIGHT_MAX_AGE_SECONDS = '600';

    /** @var list<string> */
    private readonly array $allowedOrigins;

    /**
     * @param list<string> $allowedOrigins
     */
    public function __construct(
        #[Autowire('%pro_auth.allowed_origins%')]
        array $allowedOrigins,
        private readonly SessionCookie $sessionCookie
    ) {
        $normalizedOrigins = [];
        foreach ($allowedOrigins as $allowedOrigin) {
            $allowedOrigin = rtrim(trim((string) $allowedOrigin), '/');
            if ($allowedOrigin !== '') {
                $normalizedOrigins[] = $allowedOrigin;
            }
        }

        $this->allowedOrigins = $normalizedOrigins;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Before the router (32), so preflight requests get an answer, and before the firewall (8).
            KernelEvents::REQUEST => ['onKernelRequest', 64],
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !str_starts_with($request->getPathInfo(), self::API_PATH_PREFIX)) {
            return;
        }

        if ($request->isMethod('OPTIONS') && $request->headers->has('Access-Control-Request-Method')) {
            $event->setResponse($this->preflightResponse($request));

            return;
        }

        if ($this->requiresTrustedOrigin($request) && !$this->hasTrustedOrigin($request)) {
            $event->setResponse(new Response('', Response::HTTP_FORBIDDEN));
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !str_starts_with($request->getPathInfo(), self::API_PATH_PREFIX)) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('Vary', 'Origin', false);

        $origin = $request->headers->get('Origin');
        if ($this->isAllowedCrossOrigin($origin)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
    }

    private function preflightResponse(Request $request): Response
    {
        if (!$this->isAllowedCrossOrigin($request->headers->get('Origin'))) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        return new Response('', Response::HTTP_NO_CONTENT, [
            'Access-Control-Allow-Methods' => self::ALLOWED_METHODS,
            'Access-Control-Allow-Headers' => self::ALLOWED_HEADERS,
            'Access-Control-Max-Age' => self::PREFLIGHT_MAX_AGE_SECONDS,
        ]);
    }

    private function requiresTrustedOrigin(Request $request): bool
    {
        if (in_array($request->getMethod(), self::SAFE_METHODS, true)) {
            return false;
        }

        return str_starts_with($request->getPathInfo(), self::AUTH_PATH_PREFIX)
            || $this->sessionCookie->read($request) !== null;
    }

    private function hasTrustedOrigin(Request $request): bool
    {
        $origin = $request->headers->get('Origin') ?? $this->originOf($request->headers->get('Referer'));

        if ($origin === null) {
            return false;
        }

        return $origin === $request->getSchemeAndHttpHost() || $this->isAllowedCrossOrigin($origin);
    }

    private function isAllowedCrossOrigin(?string $origin): bool
    {
        return $origin !== null && in_array($origin, $this->allowedOrigins, true);
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
