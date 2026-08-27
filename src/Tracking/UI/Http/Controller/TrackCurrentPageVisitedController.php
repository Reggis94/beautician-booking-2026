<?php

namespace App\Tracking\UI\Http\Controller;

use App\Tracking\Application\Command\TrackCurrentPageVisitedCommand;
use App\Tracking\Application\CommandHandler\TrackCurrentPageVisitedCommandHandler;
use App\Tracking\Application\Exception\TrackingRequestIsBlockedException;
use App\Tracking\Application\Exception\TrackingTooManyRequestsException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Browser usage:
 * POST /api/tracking/current-page-visited
 * Content-Type: application/json
 * Body: {"currentUrl":"https://example.com/page","referer":"https://example.com/previous"}
 *
 * The endpoint derives the IP and user agent from the request and the visitor ID from the
 * visitorId cookie. It returns 201 after tracking, 204 for excluded internal users, and an
 * intentionally opaque 403 when the tracking guard rejects the request.
 */
#[Route('/api/tracking/current-page-visited', name: 'api_tracking_track_current_page_visited', methods: ['POST'])]
class TrackCurrentPageVisitedController
{
    public function __invoke(
        Request $request,
        #[MapRequestPayload] TrackCurrentPageVisitedCommand $command,
        TrackCurrentPageVisitedCommandHandler $handler,
        #[Autowire('%tracking.demo_and_marketing_max_age%')] int $cookieLifetime,
        #[Autowire('%tracking.visitor_id_length%')] int $visitorCookieIdLength
    ): Response {
        // A previously identified internal user must not generate tracking records.
        if ($request->cookies->has('isme')) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        // Visiting the endpoint with ?isme creates the internal-user exclusion cookie.
        if ($request->query->has('isme')) {
            $newIsMeCookie = new Response('', Response::HTTP_NO_CONTENT);
            $newIsMeCookie->headers->setCookie(
                (new Cookie('isme'))
                    ->withValue('1')
                    ->withExpires(time() + $cookieLifetime)
                    ->withHttpOnly(true)
                    ->withSameSite('strict')
                    ->withSecure(false)
            );

            return $newIsMeCookie;
        }

        $userAgent = $request->headers->get('User-Agent', null);
        $visitorCookieId = $request->cookies->get('visitorId', null);
        $command->setUserAgent($userAgent);
        $command->setVisitorIdLength($visitorCookieIdLength);
        $command->setVisitorId($visitorCookieId);
        $command->setIp($request->getClientIp());
        try {
            $newVisitorCookieId = $handler($command);
        } catch (TrackingRequestIsBlockedException | TrackingTooManyRequestsException) {
            // Do not expose which guard rule, threshold, or block duration rejected the request.
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        if ($newVisitorCookieId !== null) {
            $response = new Response('', Response::HTTP_CREATED);
            $response->headers->setCookie(
                (new Cookie('visitorId'))
                    ->withValue($newVisitorCookieId)
                    ->withExpires(time() + $cookieLifetime)
                    ->withHttpOnly(true)
                    ->withSameSite('strict')
                    ->withSecure(false)
            );
        } else {
            $response = new Response('', Response::HTTP_CREATED);
        }

        return $response;
    }
}
