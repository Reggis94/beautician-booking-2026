<?php

namespace App\Booking\UI\Http\Controller\ProAdmin\Appointment;

use App\Booking\Application\Query\ListPastProAdminAppointmentsQuery;
use App\Booking\Application\QueryHandler\ListPastProAdminAppointmentsQueryHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * GET /api/pro-admin/appointments/past
 *
 * Returns past appointments for the requested professional.
 *
 * Query parameters:
 * - proId: required positive integer professional ID.
 *
 * This route is database-backed but is not called by the dashboard frontend yet.
 */
#[Route(
    '/api/pro-admin/appointments/past',
    name: 'api_pro_admin_appointments_past',
    methods: ['GET']
)]
final class ListPastProAdminAppointmentsController
{
    public function __invoke(
        #[MapQueryString] ListPastProAdminAppointmentsQuery $query,
        ListPastProAdminAppointmentsQueryHandler $handler
    ): JsonResponse {
        return new JsonResponse($handler($query));
    }
}
