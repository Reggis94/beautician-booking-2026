<?php

namespace App\ProfilePro\Application\QueryHandler;

use App\ProfilePro\Application\Query\GeocodeAddressQuery;
use App\ProfilePro\Application\Timezone\Port\GeocoderPortInterface;

final class GeocodeAddressQueryHandler
{
    public function __construct(private readonly GeocoderPortInterface $geocoder)
    {
    }

    public function __invoke(GeocodeAddressQuery $query): array
    {
        $dto = $query->getDto();
        $geocode = $this->geocoder->geocode($dto->fullAddress);

        if (!is_array($geocode)) {
            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            throw new \RuntimeException('Geocoder did not return coordinates.');
        }

        $lat = $geocode['lat'] ?? null;
        $lng = $geocode['lng'] ?? null;

        if ($lat !== null && !is_numeric($lat)) {
            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            throw new \RuntimeException('Geocoder did not return a valid latitude.');
        }

        if ($lng !== null && !is_numeric($lng)) {
            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            throw new \RuntimeException('Geocoder did not return a valid longitude.');
        }

        return [
            'lat' => $lat === null ? null : (float) $lat,
            'lng' => $lng === null ? null : (float) $lng,
        ];
    }
}

