<?php

namespace App\Tracking\Application\Command;

use Symfony\Component\Validator\Constraints as Assert;

class TrackCurrentPageVisitedCommand
{
    private ?string $userAgent = null;
    private ?string $ip = null;
    private ?string $visitorCookieId = null;

    private readonly int $visitorCookieIdLength;

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

    public function setVisitorIdLength(int $visitorCookieIdLength): void
    {
        $this->visitorCookieIdLength = $visitorCookieIdLength;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function setVisitorId(?string $visitorCookieId): void
    {
        if (!isset($this->visitorCookieIdLength)) {
            throw new \InvalidArgumentException(
                'Visitor ID length must be set from configuration parameter '
                . '"tracking.visitor_id_length" with function setVisitorIdLength(int $visitorCookieIdLength) '
                . 'before calling setVisitorId().'
            );
        }

        if (
            $visitorCookieId !== null
            && (!ctype_alnum($visitorCookieId) || mb_strlen($visitorCookieId) !== $this->visitorCookieIdLength)
        ) {
            $this->visitorCookieId = null;

            return;
        }

        $this->visitorCookieId = $visitorCookieId;
    }

    public function setIp(string $ip): void
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
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
        return $this->visitorCookieId;
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
