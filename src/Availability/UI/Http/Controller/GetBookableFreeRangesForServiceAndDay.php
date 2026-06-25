<?php

namespace App\Availability\UI\Http\Controller;

use App\Availability\Application\Query\GetBookableFreeRangesForServiceAndDayQuery;
use App\Availability\Application\QueryHandler\GetBookableFreeRangesForServiceAndDayQueryHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api/pro-presentation/bookable-free-ranges-for-service-and-day?serviceId={serviceId}&localDate={YYYY-MM-DD}
 *
 * Returns bookable local free ranges for the given service and local date,
 * ordered by start time ascending. The response end_time is the latest
 * valid start time for the requested service duration.
 *
 * Query parameters:
 * - serviceId: required integer service ID.
 * - localDate: required date in YYYY-MM-DD format.
 *
 * Successful response: array<int, array{date: string, start_time: string, end_time: string, duration_min: int}>
 */
#[Route(
    '/api/pro-presentation/bookable-free-ranges-for-service-and-day',
    name: 'get_bookable_free_ranges_for_service_and_day',
    methods: ['GET']
)]
final class GetBookableFreeRangesForServiceAndDay
{
    public function __invoke(
        #[MapQueryString] GetBookableFreeRangesForServiceAndDayQuery $query,
        GetBookableFreeRangesForServiceAndDayQueryHandler $handler
    ): JsonResponse {
        return new JsonResponse($handler($query));
    }
}
