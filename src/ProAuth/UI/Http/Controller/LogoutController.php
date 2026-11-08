<?php

namespace App\ProAuth\UI\Http\Controller;

use App\ProAuth\Application\Command\LogoutCommand;
use App\ProAuth\Application\CommandHandler\LogoutCommandHandler;
use App\ProAuth\UI\Http\Security\SessionCookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * POST /api/auth/logout
 *
 * Revokes the current session in the database and clears the session cookie.
 *
 * Successful response:
 * - HTTP 204 with an empty body, also when there is no valid session.
 *
 * Error responses:
 * - HTTP 403 when the request does not come from an allowed origin.
 */
#[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
final class LogoutController
{
    public function __invoke(Request $request, LogoutCommandHandler $handler, SessionCookie $sessionCookie): Response
    {
        $handler(new LogoutCommand($sessionCookie->read($request)));

        $response = new Response('', Response::HTTP_NO_CONTENT);
        $sessionCookie->clear($response);

        return $response;
    }
}
