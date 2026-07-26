<?php

namespace App\Tracking\Application\CommandHandler;

use App\Tracking\Application\Command\TrackCurrentPageVisitedCommand;
use App\Tracking\Application\Dao\VisitorTrackingDaoInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class TrackCurrentPageVisitedCommandHandler
{
    public function __construct(
        private readonly VisitorTrackingDaoInterface $visitorTrackingDao,
        #[Autowire('%tracking.visitor_id_length%')]
        private readonly int $visitorIdLength,
    ) {
    }

    public function __invoke(TrackCurrentPageVisitedCommand $command): ?string
    {
        if ($command->getIp() === null) {
            throw new \InvalidArgumentException('The IP address is required to track the page visit.');
        }

        $visitorId = $command->getVisitorId();
        if ($visitorId === null) {
            do {
                $visitorId = $this->generateVisitorId();
            } while ($this->visitorTrackingDao->visitorIdExists($visitorId));

            $newVisitorId = $visitorId;
        } else {
            $newVisitorId = null;
        }

        $this->visitorTrackingDao->createPageVisit(
            $visitorId,
            $command->getIp(),
            $command->getCurrentUrl(),
            $command->getReferer(),
            $command->getUserAgent()
        );

        return $newVisitorId;
    }

    public function generateVisitorId(): string
    {
        return substr(
            bin2hex(random_bytes((int) ceil($this->visitorIdLength / 2))),
            0,
            $this->visitorIdLength
        );
    }
}
