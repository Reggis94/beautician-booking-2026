<?php

namespace App\Booking\Application\Query;

use Symfony\Component\Validator\Constraints as Assert;

final class ListUpcomingProAdminAppointmentsQuery
{
    public function __construct(
        #[Assert\NotBlank(message: 'Professional id cannot be blank.')]
        #[Assert\Positive(message: 'Professional id must be positive.')]
        public readonly int $proId
    ) {
    }
}
