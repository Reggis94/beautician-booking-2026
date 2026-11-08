<?php

namespace App\Booking\UI\Http\Controller\ProAdmin\Appointment;

use App\Booking\Application\Query\ListUpcomingProAdminAppointmentsQuery;
use App\Booking\Application\QueryHandler\ListUpcomingProAdminAppointmentsQueryHandler;
use App\ProAuth\Public\AuthenticatedPro;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * GET /api/pro-admin/appointments/upcoming
 *
 * Returns upcoming appointments of the authenticated professional (session cookie required, HTTP 401 otherwise).
 * The professional is taken from the session, never from request input.
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
        #[CurrentUser] AuthenticatedPro $authenticatedPro,
        ListUpcomingProAdminAppointmentsQueryHandler $handler
    ): JsonResponse {
        return new JsonResponse($handler(new ListUpcomingProAdminAppointmentsQuery($authenticatedPro->getId())));
    }
}
