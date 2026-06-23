<?php

namespace App\Availability\Application\Query;

use Symfony\Component\Validator\Constraints as Assert;

final class GetBookableDaysForServiceQuery
{
    public function __construct(
        #[Assert\Positive()]
        public int $serviceId,
        #[Assert\NotBlank()]
        #[Assert\Regex(
            pattern: '/^\d{4}-(0[1-9]|1[0-2])$/',
            message: 'The month format must be YYYY-MM'
        )]
        public string $yearMonth
    ) {
    }
}
