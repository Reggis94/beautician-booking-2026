<?php

namespace App\Booking\Application\QueryHandler;

use App\Booking\Application\Query\ListUpcomingProAdminAppointmentsQuery;
use App\Booking\Application\Repository\Appointment\AppointmentRepositoryInterface;

final class ListUpcomingProAdminAppointmentsQueryHandler
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointmentRepository
    ) {
    }

    /**
     * @return list<array{
     *     id: int,
     *     client_name: string,
     *     service_name: ?string,
     *     start_at: string,
     *     duration_minutes: ?int
     * }>
     */
    public function __invoke(ListUpcomingProAdminAppointmentsQuery $query): array
    {
        return $this->appointmentRepository->listProAdminUpcomingAppointments($query->proId);
    }
}
