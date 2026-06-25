<?php

namespace App\Availability\UI\Http\Controller;

use App\Availability\Application\Query\GetBookableDaysForServiceQuery;
use App\Availability\Application\QueryHandler\GetBookableDaysForServiceQueryHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api/pro-presentation/bookable-days-for-service?serviceId={serviceId}&yearMonth={YYYY-MM}
 *
 * Returns the bookable local dates for the given service and month,
 * ordered by date ascending.
 *
 * Query parameters:
 * - serviceId: required integer service ID.
 * - yearMonth: required month in YYYY-MM format.
 *
 * Successful response: array<int, string>
 */
#[Route(
    '/api/pro-presentation/bookable-days-for-service',
    name: 'get_bookable_days_for_service',
    methods: ['GET']
)]
final class GetBookableDaysForService
{
    public function __invoke(
        #[MapQueryString] GetBookableDaysForServiceQuery $query,
        GetBookableDaysForServiceQueryHandler $handler
    ): JsonResponse {
        return new JsonResponse($handler($query));
    }
}
