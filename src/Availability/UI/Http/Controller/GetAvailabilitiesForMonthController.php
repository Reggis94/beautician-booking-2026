<?php

namespace App\Availability\UI\Http\Controller;

use App\Availability\Application\Exception\PastMonthAvailabilityRequestException;
use App\Availability\Application\Query\GetAvailabilitiesForMonthQuery;
use App\Availability\Application\QueryHandler\GetAvailabilitiesForMonthQueryHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api/availabilities?proId={proId}&month={YYYY-MM}
 *
 * Returns opening hours by local date for the given pro and month.
 * Dates omitted from the response are considered closed.
 *
 * Query parameters:
 * - proId: required integer pro ID.
 * - month: required month in YYYY-MM format, current month or future month only.
 *
 * Successful response: array<string, array{startTimeLocal: string, endTimeLocal: string}>
 */
#[Route('/api/availabilities', name: 'get_availabilities_for_month', methods: ['GET'])]
final class GetAvailabilitiesForMonthController
{
    public function __invoke(
        #[MapQueryString] GetAvailabilitiesForMonthQuery $query,
        GetAvailabilitiesForMonthQueryHandler $handler
    ): JsonResponse {
        try {
            return new JsonResponse($handler($query));
        } catch (PastMonthAvailabilityRequestException $exception) {
            return new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }
    }
}
