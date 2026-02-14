<?php

namespace App\ProfilePro\Application\Timezone\Port;

interface GeocoderPortInterface
{
    public function geocode(string $address);
}
