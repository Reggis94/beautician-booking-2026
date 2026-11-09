<?php

namespace App\Services\Application\Service\Command;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Mapped from the JSON body of DELETE /api/pro/service/delete.
 */
final class DeleteServiceCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public readonly ?int $serviceId = null
    ) {
    }
}
