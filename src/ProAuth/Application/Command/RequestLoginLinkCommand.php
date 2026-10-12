<?php

namespace App\ProAuth\Application\Command;

use Symfony\Component\Validator\Constraints as Assert;

final class RequestLoginLinkCommand
{
    public function __construct(
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Email(normalizer: 'trim')]
        #[Assert\Length(max: 255, normalizer: 'trim')]
        public readonly string $email
    ) {
    }
}
