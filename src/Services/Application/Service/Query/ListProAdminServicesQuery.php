<?php

namespace App\Services\Application\Service\Query;

use Symfony\Component\Validator\Constraints as Assert;

final class ListProAdminServicesQuery
{
    public function __construct(
        #[Assert\NotBlank(message: 'Professional id cannot be blank.')]
        #[Assert\Positive(message: 'Professional id must be positive.')]
        public readonly int $proId
    ) {
    }
}
