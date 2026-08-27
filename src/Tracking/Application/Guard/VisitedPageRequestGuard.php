<?php

namespace App\Tracking\Application\Guard;

use App\Tracking\Application\Enum\IdentifierBlockReason;
use App\Tracking\Application\Exception\TrackingRequestIsBlockedException;
use App\Tracking\Application\Exception\TrackingTooManyRequestsException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class VisitedPageRequestGuard implements VisitedPageRequestGuardInterface
{
    public function __construct(
        #[Autowire('%tracking.guard.short.limit%')]
        private readonly int $shortLimit,
        #[Autowire('%tracking.guard.short.window_seconds%')]
        private readonly int $shortWindowSeconds,
        #[Autowire('%tracking.guard.medium.limit%')]
        private readonly int $mediumLimit,
        #[Autowire('%tracking.guard.medium.window_seconds%')]
        private readonly int $mediumWindowSeconds,
        #[Autowire('%tracking.guard.long.limit%')]
        private readonly int $longLimit,
        #[Autowire('%tracking.guard.long.window_seconds%')]
        private readonly int $longWindowSeconds,
        #[Autowire('%tracking.guard.block_seconds%')]
        private readonly int $blockSeconds,
        #[Autowire('%tracking.guard.user_agent_min_length%')]
        private readonly int $userAgentMinLength,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
    }

    public function guard(
        string $ip,
        ?string $visitorCookieId,
        ?string $userAgent,
        callable $isBlocked,
        callable $findExceededRateLimit,
        callable $blockIdentifiers
    ): void {
        if (!$this->isAllowed(!$isBlocked($ip, $visitorCookieId))) {
            throw new TrackingRequestIsBlockedException('identifier is currently blocked');
        }

        if (!$this->isAllowed($this->isUserAgentAllowed($userAgent))) {
            $blockIdentifiers(
                $ip,
                $visitorCookieId,
                $this->blockSeconds,
                IdentifierBlockReason::UserAgentRejected,
                null
            );

            throw new TrackingRequestIsBlockedException('identifier is currently blocked');
        }

        $rateLimit = $this->findExceededRateLimit($ip, $visitorCookieId, $findExceededRateLimit);
        if (!$this->isAllowed($rateLimit === null)) {
            $blockIdentifiers(
                $rateLimit['ip'],
                $rateLimit['visitor_id_cookie'],
                $this->blockSeconds,
                $rateLimit['reason'],
                $rateLimit['window_seconds']
            );

            throw new TrackingTooManyRequestsException('The tracking request rate was exceeded.');
        }
    }

    private function isUserAgentAllowed(?string $userAgent): bool
    {
        if ($this->environment === 'dev' && str_contains($userAgent ?? '', 'Postman')) {
            return true;
        }

        return mb_strlen($userAgent ?? '') >= $this->userAgentMinLength;
    }

    /**
     * @return null|array{
     *     ip: ?string,
     *     visitor_id_cookie: ?string,
     *     window_seconds: int,
     *     reason: IdentifierBlockReason
     * }
     */
    private function findExceededRateLimit(
        string $ip,
        ?string $visitorCookieId,
        callable $findExceededRateLimit
    ): ?array {
        return $findExceededRateLimit($ip, $visitorCookieId, $this->rateLimitTiers());
    }

    /**
     * @return list<array{
     *     limit: int,
     *     window_seconds: int,
     *     reason: IdentifierBlockReason
     * }>
     */
    private function rateLimitTiers(): array
    {
        return [
            [
                'limit' => $this->shortLimit,
                'window_seconds' => $this->shortWindowSeconds,
                'reason' => IdentifierBlockReason::ShortRateLimit,
            ],
            [
                'limit' => $this->mediumLimit,
                'window_seconds' => $this->mediumWindowSeconds,
                'reason' => IdentifierBlockReason::MediumRateLimit,
            ],
            [
                'limit' => $this->longLimit,
                'window_seconds' => $this->longWindowSeconds,
                'reason' => IdentifierBlockReason::LongRateLimit,
            ],
        ];
    }

    private function isAllowed(bool $allowed): bool
    {
        return $allowed;
    }
}
