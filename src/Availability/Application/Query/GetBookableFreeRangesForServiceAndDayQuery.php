<?php

namespace App\Availability\Application\Query;

use Symfony\Component\Validator\Constraints as Assert;

final class GetBookableFreeRangesForServiceAndDayQuery
{
    public function __construct(
        #[Assert\Positive()]
        public int $serviceId,
        #[Assert\NotBlank()]
        #[Assert\Date(
            message: 'The date format must be YYYY-MM-DD'
        )]
        public string $localDate
    ) {
    }
}
