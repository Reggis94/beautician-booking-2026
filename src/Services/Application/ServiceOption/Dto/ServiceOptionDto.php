<?php

namespace App\Services\Application\ServiceOption\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ServiceOptionDto
{
    #[Assert\NotBlank(message: 'Option name cannot be empty.', normalizer: 'trim')]
    #[Assert\Length(max: 100, maxMessage: 'Option name must be at most 100 characters.', normalizer: 'trim')]
    public readonly string $name;

    #[Assert\Positive(message: 'Service id must be positive.')]
    public readonly int $serviceId;

    #[Assert\Positive(message: 'Professional id must be positive.')]
    public readonly int $proId;

    #[Assert\PositiveOrZero(message: 'Extra price must be zero or positive.')]
    public readonly ?int $priceExtraCents;

    public function __construct(string $name, int $serviceId, int $proId, ?int $priceExtraCents)
    {
        $this->name = trim($name);
        $this->serviceId = $serviceId;
        $this->proId = $proId;
        $this->priceExtraCents = $priceExtraCents;
    }
}
