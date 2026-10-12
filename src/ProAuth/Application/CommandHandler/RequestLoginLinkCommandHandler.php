<?php

namespace App\ProAuth\Application\CommandHandler;

use App\ProAuth\Application\Command\RequestLoginLinkCommand;
use App\ProAuth\Application\Dao\LoginTokenDaoInterface;
use App\ProAuth\Application\Mailer\LoginLinkMailerInterface;
use App\ProAuth\Application\Throttling\LoginLinkCooldown;
use App\ProAuth\Application\Throttling\LoginLinkRateLimit;
use App\ProAuth\Application\Token\OpaqueToken;
use Psr\Log\LoggerInterface;

/**
 * Sends a single-use login link to an existing beautician, unless the cooldown or the rate limit refuses it.
 *
 * The outcome is never returned: the caller answers the same way whether the email exists, the request was
 * refused, or the email could not be delivered.
 */
final class RequestLoginLinkCommandHandler
{
    public function __construct(
        private readonly LoginTokenDaoInterface $loginTokenDao,
        private readonly LoginLinkCooldown $loginLinkCooldown,
        private readonly LoginLinkRateLimit $loginLinkRateLimit,
        private readonly LoginLinkMailerInterface $loginLinkMailer,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(RequestLoginLinkCommand $command): void
    {
        $email = mb_strtolower(trim($command->email));
        $proId = $this->loginTokenDao->findActiveProIdByEmail($email);

        if ($proId === null) {
            return;
        }

        $loginToken = OpaqueToken::generate();
        $loginTokenHash = OpaqueToken::hash($loginToken);

        $this->loginTokenDao->beginTransaction();

        try {
            // Serializes concurrent requests of the same beautician, so parallel requests cannot bypass the limits.
            $this->loginTokenDao->lockPro($proId);
            $isAllowed = !$this->loginLinkCooldown->isActive($proId)
                && !$this->loginLinkRateLimit->isReached($proId);

            // A refused request stores nothing.
            if ($isAllowed) {
                $this->loginTokenDao->create($proId, $loginTokenHash);
            }

            $this->loginTokenDao->commit();
        } catch (\Throwable $exception) {
            $this->loginTokenDao->rollBack();
            throw $exception;
        }

        if (!$isAllowed) {
            return;
        }

        try {
            $this->loginLinkMailer->sendLoginLink($email, $loginToken);
        } catch (\Throwable $exception) {
            // Any failure, not only a transport one: the response must stay the same as a sent link.
            // The attempt is kept so it counts in the cooldown and the rate limit, but the link never reached the
            // beautician, so its token must not stay usable. Never log the token or the exception trace, whose
            // arguments may contain it.
            $this->loginTokenDao->markDeliveryFailed($loginTokenHash);
            $this->logger->error('Login link email delivery failed.', [
                'pro_id' => $proId,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);
        }
    }
}
