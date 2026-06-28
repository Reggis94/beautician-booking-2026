<?php

namespace App\Booking\Application\QueryHandler;

use App\Booking\Application\Query\ListPastProAdminAppointmentsQuery;
use App\Booking\Application\Repository\Appointment\AppointmentRepositoryInterface;

final class ListPastProAdminAppointmentsQueryHandler
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
    public function __invoke(ListPastProAdminAppointmentsQuery $query): array
    {
        return $this->appointmentRepository->listProAdminPastAppointments($query->proId);
    }
}
