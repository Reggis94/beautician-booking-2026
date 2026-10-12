<?php

namespace App\Services\UI\Http\Controller\ProAdmin\Service;

use App\ProAuth\Public\AuthenticatedPro;
use App\Services\Application\Service\Query\ListProAdminServicesQuery;
use App\Services\Application\Service\QueryHandler\ListProAdminServicesQueryHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * GET /api/pro-admin/services
 *
 * Returns services managed by the authenticated professional (session cookie required, HTTP 401 otherwise).
 * The professional is taken from the session, never from request input.
 *
 * This route is database-backed but is not called by the dashboard frontend yet.
 */
#[Route('/api/pro-admin/services', name: 'api_pro_admin_services_index', methods: ['GET'])]
final class ListProAdminServicesController
{
    public function __invoke(
        #[CurrentUser] AuthenticatedPro $authenticatedPro,
        ListProAdminServicesQueryHandler $handler
    ): JsonResponse {
        return new JsonResponse($handler(new ListProAdminServicesQuery($authenticatedPro->getId())));
    }
}
