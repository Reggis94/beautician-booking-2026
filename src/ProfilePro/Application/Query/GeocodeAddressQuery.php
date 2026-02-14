<?php

namespace App\ProfilePro\Application\Query;

use App\ProfilePro\Application\Timezone\Dto\GeocodeAddressDto;

final readonly class GeocodeAddressQuery
{
    public function __construct(private GeocodeAddressDto $dto)
    {
    }

    public function getDto(): GeocodeAddressDto
    {
        return $this->dto;
    }
}
