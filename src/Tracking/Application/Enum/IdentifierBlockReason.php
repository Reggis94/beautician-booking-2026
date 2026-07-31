<?php

namespace App\Tracking\Application\Enum;

enum IdentifierBlockReason: string
{
    case ShortRateLimit = 'tracking_visit_cookie.60_requests_per_2_minutes';
    case MediumRateLimit = 'tracking_visit_cookie.120_requests_per_2_hours';
    case LongRateLimit = 'tracking_visit_cookie.300_requests_per_24_hours';
    case UserAgentRejected = 'tracking_visit_cookie.user_agent_rejected';

    public function description(): string
    {
        return match ($this) {
            self::ShortRateLimit => '60 requests are allowed per 2 minutes; exceeding this blocks for 24 hours',
            self::MediumRateLimit => '120 requests are allowed per 2 hours; exceeding this blocks for 24 hours',
            self::LongRateLimit => '300 requests are allowed per 24 hours; exceeding this blocks for 24 hours',
            self::UserAgentRejected => 'User agent had fewer than 20 characters; blocked for 24 hours',
        };
    }
}
