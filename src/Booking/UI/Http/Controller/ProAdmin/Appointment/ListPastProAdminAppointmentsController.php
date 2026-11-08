<?php

namespace App\Booking\UI\Http\Controller\ProAdmin\Appointment;

use App\Booking\Application\Query\ListPastProAdminAppointmentsQuery;
use App\Booking\Application\QueryHandler\ListPastProAdminAppointmentsQueryHandler;
use App\ProAuth\Public\AuthenticatedPro;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * GET /api/pro-admin/appointments/past
 *
 * Returns past appointments of the authenticated professional (session cookie required, HTTP 401 otherwise).
 * The professional is taken from the session, never from request input.
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
        #[CurrentUser] AuthenticatedPro $authenticatedPro,
        ListPastProAdminAppointmentsQueryHandler $handler
    ): JsonResponse {
        return new JsonResponse($handler(new ListPastProAdminAppointmentsQuery($authenticatedPro->getId())));
    }
}
