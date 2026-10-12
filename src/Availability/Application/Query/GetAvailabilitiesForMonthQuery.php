<?php

namespace App\Availability\Application\Query;

use Symfony\Component\Validator\Constraints as Assert;

final class GetAvailabilitiesForMonthQuery
{
    private int $proId;

    public function __construct(
        #[Assert\NotBlank()]
        #[Assert\Regex(
            pattern: '/^\d{4}-(0[1-9]|1[0-2])$/',
            message: 'The month format must be YYYY-MM'
        )]
        public string $month
    ) {
    }

    /**
     * Set from the authenticated professional, never from request input.
     */
    public function setProId(int $proId): void
    {
        $this->proId = $proId;
    }

    public function getProId(): int
    {
        return $this->proId;
    }
}
