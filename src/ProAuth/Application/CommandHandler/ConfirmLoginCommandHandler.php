<?php

namespace App\ProAuth\Application\CommandHandler;

use App\ProAuth\Application\Command\ConfirmLoginCommand;
use App\ProAuth\Application\Dao\LoginTokenDaoInterface;
use App\ProAuth\Application\Dao\SessionDaoInterface;
use App\ProAuth\Application\Exception\InvalidLoginTokenException;
use App\ProAuth\Application\Token\OpaqueToken;

final class ConfirmLoginCommandHandler
{
    public function __construct(
        private readonly LoginTokenDaoInterface $loginTokenDao,
        private readonly SessionDaoInterface $sessionDao
    ) {
    }

    /**
     * Consumes the login token and opens a new session.
     *
     * @return string The new session identifier, generated independently from the login token.
     *
     * @throws InvalidLoginTokenException
     */
    public function __invoke(ConfirmLoginCommand $command): string
    {
        $proId = $this->loginTokenDao->consume(OpaqueToken::hash($command->token));

        if ($proId === null) {
            throw new InvalidLoginTokenException();
        }

        // Never keep a session identifier that existed before login (session fixation).
        $previousSessionId = $command->getPreviousSessionId();
        if ($previousSessionId !== null) {
            $this->sessionDao->revoke(OpaqueToken::hash($previousSessionId));
        }

        $sessionId = OpaqueToken::generate();
        $this->sessionDao->create($proId, OpaqueToken::hash($sessionId));

        return $sessionId;
    }
}
