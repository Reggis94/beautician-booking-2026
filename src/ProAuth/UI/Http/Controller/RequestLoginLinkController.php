<?php

namespace App\ProAuth\UI\Http\Controller;

use App\ProAuth\Application\Command\RequestLoginLinkCommand;
use App\ProAuth\Application\CommandHandler\RequestLoginLinkCommandHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * POST /api/auth/login-link
 *
 * Emails a single-use login link to the beautician owning the email address.
 *
 * JSON request body:
 * - email: required email address.
 *
 * Successful response:
 * - HTTP 202 with an empty body, whether or not the email belongs to a beautician, the request was refused
 *   (less than 60 seconds since the previous link, or 24 links over the last hour) or the email could be
 *   delivered. The response never reveals any of these.
 *
 * Error responses:
 * - HTTP 422 when the email is missing or malformed.
 * - HTTP 403 when the request does not come from an allowed origin.
 */
#[Route('/api/auth/login-link', name: 'api_auth_request_login_link', methods: ['POST'])]
final class RequestLoginLinkController
{
    public function __invoke(
        #[MapRequestPayload] RequestLoginLinkCommand $command,
        RequestLoginLinkCommandHandler $handler
    ): Response {
        $handler($command);

        return new Response('', Response::HTTP_ACCEPTED);
    }
}
