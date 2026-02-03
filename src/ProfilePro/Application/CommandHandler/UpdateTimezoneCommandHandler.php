<?php

namespace App\ProfilePro\Application\CommandHandler;

use App\ProfilePro\Application\Command\UpdateTimezoneCommand;
use App\ProfilePro\Application\Timezone\Dao\TimezoneDao;
use App\ProfilePro\Application\Timezone\Port\TimezoneResolverPortInterface;

final class UpdateTimezoneCommandHandler
{
    public function __construct(
        private readonly TimezoneResolverPortInterface $timezoneResolver,
        private readonly TimezoneDao $timezoneDao
    ) {
    }

    public function __invoke(UpdateTimezoneCommand $command): void
    {
        $timezoneDto = $command->getTimezoneDto();
        $proId = $timezoneDto->getProId();
        $fullAddress = $timezoneDto->getFullAddress();
        $lat = $timezoneDto->getLat();
        $lng = $timezoneDto->getLng();

        // Invariant duplicated with TimezoneDto: if you change lat/lng bounds here, update TimezoneDto too.
        if ($lat < -90.0 || $lat > 90.0) {
            throw new \InvalidArgumentException('lat must be between -90 and 90.');
        }

        // Invariant duplicated with TimezoneDto: if you change lat/lng bounds here, update TimezoneDto too.
        if ($lng < -180.0 || $lng > 180.0) {
            throw new \InvalidArgumentException('lng must be between -180 and 180.');
        }

        $timezone = $this->timezoneResolver->resolveTimezone($lat, $lng);

        if (!is_string($timezone) || trim($timezone) === '') {
            throw new \InvalidArgumentException('timezone must be a non-empty string.');
        }

        $this->timezoneDao->updateForPro($proId, $fullAddress, $lat, $lng, $timezone);
    }
}
