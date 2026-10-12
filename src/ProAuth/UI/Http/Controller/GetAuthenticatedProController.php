<?php

namespace App\ProAuth\UI\Http\Controller;

use App\ProAuth\Public\AuthenticatedPro;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * GET /api/auth/me
 *
 * Returns the authentication status: the profile of the beautician identified by the session cookie.
 *
 * Successful response: HTTP 200 with array{
 *     email: string,
 *     link_slug: string,
 *     display_name: ?string,
 *     firstname: ?string,
 *     lastname: ?string
 * }
 *
 * Error responses:
 * - HTTP 401 with an empty body when the session cookie is missing, invalid or revoked.
 */
#[Route('/api/auth/me', name: 'api_auth_me', methods: ['GET'])]
final class GetAuthenticatedProController
{
    public function __invoke(#[CurrentUser] AuthenticatedPro $authenticatedPro): JsonResponse
    {
        $response = new JsonResponse([
            'email' => $authenticatedPro->getEmail(),
            'link_slug' => $authenticatedPro->getLinkSlug(),
            'display_name' => $authenticatedPro->getDisplayName(),
            'firstname' => $authenticatedPro->getFirstname(),
            'lastname' => $authenticatedPro->getLastname(),
        ]);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
