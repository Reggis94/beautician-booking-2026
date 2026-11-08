<?php

namespace App\ProAuth\Application\CommandHandler;

use App\ProAuth\Application\Command\LogoutCommand;
use App\ProAuth\Application\Dao\SessionDaoInterface;
use App\ProAuth\Application\Token\OpaqueToken;

final class LogoutCommandHandler
{
    public function __construct(private readonly SessionDaoInterface $sessionDao)
    {
    }

    /**
     * Revokes the session for good. Does nothing when there is no session or it is already revoked.
     */
    public function __invoke(LogoutCommand $command): void
    {
        if ($command->sessionId === null) {
            return;
        }

        $this->sessionDao->revoke(OpaqueToken::hash($command->sessionId));
    }
}
