<?php

namespace App\Services\UI\Http\Controller\ProAdmin\Service;

use App\Services\Application\Service\Query\ListProAdminServicesQuery;
use App\Services\Application\Service\QueryHandler\ListProAdminServicesQueryHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api/pro-admin/services
 *
 * Returns services managed by the requested professional.
 *
 * Query parameters:
 * - proId: required positive integer professional ID.
 *
 * This route is database-backed but is not called by the dashboard frontend yet.
 */
#[Route('/api/pro-admin/services', name: 'api_pro_admin_services_index', methods: ['GET'])]
final class ListProAdminServicesController
{
    public function __invoke(
        #[MapQueryString] ListProAdminServicesQuery $query,
        ListProAdminServicesQueryHandler $handler
    ): JsonResponse {
        return new JsonResponse($handler($query));
    }
}
