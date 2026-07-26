<?php

namespace App\Tracking\Application\Command;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TrackCurrentPageVisitedCommand
{
    private ?string $userAgent = null;
    private ?string $ip = null;
    private ?string $visitorId = null;

    private readonly int $visitorIdLength;

    public function __construct(
        #[Assert\Url(
            normalizer: 'trim',
            message: 'The url {{ value }} is not a valid url'
        )]
        private readonly string $currentUrl,
        #[Assert\Url(
            normalizer: 'trim',
            message: 'The url {{ value }} is not a valid url'
        )]
        private readonly ?string $referer
    ) {
    }

    public function setVisitorIdLength(int $visitorIdLength): void
    {
        $this->visitorIdLength = $visitorIdLength;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function setVisitorId(?string $visitorId): void
    {
        if (! isset($this->visitorIdLength)) {
            throw new \InvalidArgumentException('Visitor ID length must be set from configuration parameter "tracking.visitor_id_length" with function setVisitorIdLength(int $visitorIdLength) before calling setVisitorId().');
        }

        if ($visitorId !== null && (! ctype_alnum($visitorId) || mb_strlen($visitorId) !== $this->visitorIdLength)) {
            $this->visitorId = null;

            return;
        }

        $this->visitorId = $visitorId;
    }

    public function setIp(string $ip): void
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Must be a valid IP');
        }

        $this->ip = $ip;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function getVisitorId(): ?string
    {
        return $this->visitorId;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getCurrentUrl(): string
    {
        return $this->currentUrl;
    }

    public function getReferer(): ?string
    {
        return $this->referer;
    }
}
