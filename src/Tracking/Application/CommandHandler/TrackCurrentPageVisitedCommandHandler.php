<?php

namespace App\Tracking\Application\CommandHandler;

use App\Tracking\Application\Command\TrackCurrentPageVisitedCommand;
use App\Tracking\Application\Dao\VisitorTrackingDaoInterface;
use App\Tracking\Application\Exception\TrackingRequestIsBlockedException;
use App\Tracking\Application\Exception\TrackingTooManyRequestsException;
use App\Tracking\Application\Guard\VisitedPageRequestGuardInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class TrackCurrentPageVisitedCommandHandler
{
    public function __construct(
        private readonly VisitorTrackingDaoInterface $visitorTrackingDao,
        private readonly VisitedPageRequestGuardInterface $guard,
        #[Autowire('%tracking.visitor_id_length%')]
        private readonly int $visitorCookieIdLength,
    ) {
    }

    public function __invoke(TrackCurrentPageVisitedCommand $command): ?string
    {
        if ($command->getIp() === null) {
            throw new \InvalidArgumentException('The IP address is required to track the page visit.');
        }

        $visitorCookieId = $command->getVisitorId();
        $ip = $command->getIp();
        $isBlocked = [$this->visitorTrackingDao, 'isBlocked'];
        $findExceededRateLimit = [$this->visitorTrackingDao, 'findExceededRateLimit'];
        $blockIdentifiers = [$this->visitorTrackingDao, 'blockIdentifiers'];

        $this->visitorTrackingDao->beginTransaction();

        try {
            $this->visitorTrackingDao->lockPageVisitWrites();
            $this->guard->guard(
                $ip,
                $visitorCookieId,
                $command->getUserAgent(),
                $isBlocked,
                $findExceededRateLimit,
                $blockIdentifiers
            );

            if ($visitorCookieId === null) {
                do {
                    $visitorCookieId = $this->generateVisitorId();
                } while ($this->visitorTrackingDao->visitorIdExists($visitorCookieId));

                $newVisitorCookieId = $visitorCookieId;
            } else {
                $newVisitorCookieId = null;
            }

            $this->visitorTrackingDao->createPageVisit(
                $visitorCookieId,
                $ip,
                $command->getCurrentUrl(),
                $command->getReferer(),
                $command->getUserAgent()
            );
            $this->visitorTrackingDao->commit();

            return $newVisitorCookieId;
        } catch (TrackingRequestIsBlockedException | TrackingTooManyRequestsException $exception) {
            $this->visitorTrackingDao->commit();

            throw $exception;
        } catch (\Throwable $exception) {
            $this->visitorTrackingDao->rollBack();

            throw $exception;
        }
    }

    public function generateVisitorId(): string
    {
        return substr(
            bin2hex(random_bytes((int) ceil($this->visitorCookieIdLength / 2))),
            0,
            $this->visitorCookieIdLength
        );
    }
}
