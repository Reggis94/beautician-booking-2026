<?php

namespace App\Booking\Application\Command;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateAppointmentByClientCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $proId,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $serviceId,
        #[Assert\NotBlank]
        #[Assert\DateTime]
        public string $startDateTimeLocal,
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(
            min: 2,
            max: 50
        )]
        #[Assert\Regex(
            pattern: "/^[\p{L}][\p{L}\s'-]*$/u",
            message: 'This value should contain only letters, spaces, hyphens, or apostrophes.'
        )]
        public string $lastName,
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(
            min: 2,
            max: 50
        )]
        #[Assert\Regex(
            pattern: "/^[\p{L}][\p{L}\s'-]*$/u",
            message: 'This value should contain only letters, spaces, hyphens, or apostrophes.'
        )]
        public string $firstName,
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Email]
        public string $email,
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(
            min: 7,
            max: 30
        )]
        public string $extraPhone
    ) {
    }
}
