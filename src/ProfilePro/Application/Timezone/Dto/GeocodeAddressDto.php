<?php

namespace App\ProfilePro\Application\Timezone\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class GeocodeAddressDto
{
    #[Assert\NotBlank(message: 'full_address must be a non-empty string.', normalizer: 'trim')]
    #[Assert\Length(max: 255, maxMessage: 'full_address must be at most 255 characters.', normalizer: 'trim')]
    public readonly string $fullAddress;

    public function __construct(string $fullAddress)
    {
        $this->fullAddress = trim($fullAddress);
    }
}
