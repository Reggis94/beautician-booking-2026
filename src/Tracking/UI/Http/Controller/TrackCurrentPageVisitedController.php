<?php

namespace App\Tracking\UI\Http\Controller;

use App\Tracking\Application\Command\TrackCurrentPageVisitedCommand;
use App\Tracking\Application\CommandHandler\TrackCurrentPageVisitedCommandHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tracking/current-page-visited', name: 'api_tracking_track_current_page_visited', methods: ['POST'])]
class TrackCurrentPageVisitedController
{
    public function __invoke(
        Request $request,
        #[MapRequestPayload] TrackCurrentPageVisitedCommand $command,
        TrackCurrentPageVisitedCommandHandler $handler,
        #[Autowire('%tracking.demo_and_marketing_max_age%')] int $cookieLifetime,
        #[Autowire('%tracking.visitor_id_length%')] int $visitorIdLength
    ): Response {
        if ($request->cookies->has('isme')) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

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
        $visitorId = $request->cookies->get('visitorId', null);
        $command->setUserAgent($userAgent);
        $command->setVisitorIdLength($visitorIdLength);
        $command->setVisitorId($visitorId);
        $command->setIp($request->getClientIp());
        $newVisitorId = $handler($command);

        if ($newVisitorId !== null) {
            $response = new Response('', Response::HTTP_CREATED);
            $response->headers->setCookie(
                (new Cookie('visitorId'))
                    ->withValue($newVisitorId)
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
