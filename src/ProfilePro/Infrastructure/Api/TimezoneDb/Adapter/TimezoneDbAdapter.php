<?php

declare(strict_types=1);

namespace App\ProfilePro\Infrastructure\Api\TimezoneDb\Adapter;

use App\ProfilePro\Application\Timezone\Port\TimezoneResolverPortInterface;

final class TimezoneDbAdapter implements TimezoneResolverPortInterface
{
    public function resolveTimezone(float $lat, float $lng): string
    {
        $apiKey = $_ENV['TIMEZONEDB_KEY'] ?? $_SERVER['TIMEZONEDB_KEY'] ?? getenv('TIMEZONEDB_KEY');
        if (!is_string($apiKey) || $apiKey === '') {
            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            throw new \RuntimeException('TimezoneDB API key is not configured.');
        }

        $query = http_build_query([
            'key' => $apiKey,
            'by' => 'position',
            'lat' => $lat,
            'lng' => $lng
        ]);

        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
            ],
        ]);

        $xmlResponse = file_get_contents('https://api.timezonedb.com/v2.1/get-time-zone?' . $query, false, $context);
        if ($xmlResponse === false || $xmlResponse === '') {
            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            throw new \RuntimeException('Failed to fetch timezone from TimezoneDB.');
        }
        $xml = simplexml_load_string($xmlResponse);
        if ($xml === false) {
            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            throw new \RuntimeException('Invalid XML response from TimezoneDB.');
        }

        $zoneName = '';
        if (isset($xml->zoneName)) {
            $zoneName = (string) $xml->zoneName;
        }

        if ($zoneName === '') {
            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            throw new \RuntimeException(
                'TimezoneDB response did not include a zoneName. Response: ' . $xmlResponse
            );
        }

        static $knownTimezones = null;
        if ($knownTimezones === null) {
            $knownTimezones = array_fill_keys(timezone_identifiers_list(), true);
        }

        if (!isset($knownTimezones[$zoneName])) {
            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            throw new \RuntimeException('Invalid PHP timezone identifier: ' . $zoneName);
        }

        return $zoneName;
    }
}

