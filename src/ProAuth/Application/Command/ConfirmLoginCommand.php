<?php

namespace App\ProAuth\Application\Command;

use App\ProAuth\Application\Token\OpaqueToken;
use Symfony\Component\Validator\Constraints as Assert;

final class ConfirmLoginCommand
{
    private ?string $previousSessionId = null;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: OpaqueToken::PATTERN)]
        public readonly string $token
    ) {
    }

    /**
     * Session identifier already carried by the browser, if any. It is revoked on login to prevent session fixation.
     */
    public function setPreviousSessionId(?string $previousSessionId): void
    {
        $this->previousSessionId = $previousSessionId;
    }

    public function getPreviousSessionId(): ?string
    {
        return $this->previousSessionId;
    }
}
