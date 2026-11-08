<?php

namespace App\ProAuth\UI\Http\Controller;

use App\ProAuth\Application\Command\ConfirmLoginCommand;
use App\ProAuth\Application\CommandHandler\ConfirmLoginCommandHandler;
use App\ProAuth\Application\Exception\InvalidLoginTokenException;
use App\ProAuth\UI\Http\Security\SessionCookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * POST /api/auth/login
 *
 * Consumes the token from the emailed login link and opens a session. Opening the link itself does not
 * authenticate: only this request, sent by the frontend confirmation page, does.
 *
 * JSON request body:
 * - token: required login token, read by the frontend from the login link URL fragment (#token=...).
 *
 * Successful response:
 * - HTTP 204 with an empty body and a Set-Cookie header holding a new HTTP-only session cookie.
 *
 * Error responses:
 * - HTTP 401 with an empty body when the token is unknown or already used.
 * - HTTP 422 when the token is missing or malformed.
 * - HTTP 403 when the request does not come from an allowed origin.
 */
#[Route('/api/auth/login', name: 'api_auth_confirm_login', methods: ['POST'])]
final class ConfirmLoginController
{
    public function __invoke(
        Request $request,
        #[MapRequestPayload] ConfirmLoginCommand $command,
        ConfirmLoginCommandHandler $handler,
        SessionCookie $sessionCookie
    ): Response {
        $command->setPreviousSessionId($sessionCookie->read($request));

        try {
            $sessionId = $handler($command);
        } catch (InvalidLoginTokenException) {
            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        $response = new Response('', Response::HTTP_NO_CONTENT);
        $sessionCookie->set($response, $sessionId);

        return $response;
    }
}
