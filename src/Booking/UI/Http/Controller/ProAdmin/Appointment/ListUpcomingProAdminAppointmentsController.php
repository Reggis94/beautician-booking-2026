<?php

namespace App\Booking\UI\Http\Controller\ProAdmin\Appointment;

use App\Booking\Application\Query\ListUpcomingProAdminAppointmentsQuery;
use App\Booking\Application\QueryHandler\ListUpcomingProAdminAppointmentsQueryHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api/pro-admin/appointments/upcoming
 *
 * Returns upcoming appointments for the requested professional.
 *
 * Query parameters:
 * - proId: required positive integer professional ID.
 *
 * This route is database-backed but is not called by the dashboard frontend yet.
 */
#[Route(
    '/api/pro-admin/appointments/upcoming',
    name: 'api_pro_admin_appointments_upcoming',
    methods: ['GET']
)]
final class ListUpcomingProAdminAppointmentsController
{
    public function __invoke(
        #[MapQueryString] ListUpcomingProAdminAppointmentsQuery $query,
        ListUpcomingProAdminAppointmentsQueryHandler $handler
    ): JsonResponse {
        return new JsonResponse($handler($query));
    }
}
