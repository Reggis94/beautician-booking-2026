<?php

namespace App\ProfilePro\Application\Timezone\Port;

interface TimezoneResolverPortInterface
{
    /**
     * Resolve the PHP timezone identifier (e.g. "Europe/Paris") for coordinates.
     *
     * Implementations must return a valid PHP timezone string or throw an error.
     *
     * @throws \RuntimeException If the resolved timezone is not a valid PHP timezone identifier.
     */
    public function resolveTimezone(float $lat, float $lng): string;
}
