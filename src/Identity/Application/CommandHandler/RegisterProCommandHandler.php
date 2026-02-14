<?php

namespace App\Identity\Application\CommandHandler;

use App\Identity\Application\Command\RegisterProCommand;
use App\Identity\Application\Dao\ProRegistrationDaoInterface;
use App\Identity\Application\Port\ProConfirmationEmailSenderInterface;

final class RegisterProCommandHandler
{
    public function __construct(
        private readonly ProRegistrationDaoInterface $proRegistrationDao,
        private readonly ProConfirmationEmailSenderInterface $emailSender
    ) {
    }

    public function __invoke(RegisterProCommand $command): void
    {
        $confirmationToken = bin2hex(random_bytes(32));

        $this->proRegistrationDao->create(
            $command->firstName,
            $command->lastName,
            $command->email,
            $confirmationToken
        );

        $this->emailSender->sendConfirmationEmail($command->email, $confirmationToken);
    }
}
