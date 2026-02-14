<?php

declare(strict_types=1);

namespace App\ProfilePro\Infrastructure\Api\TimezoneDb\Adapter;

use App\ProfilePro\Application\Timezone\Port\GeocoderPortInterface;

final class GeocoderAdapter implements GeocoderPortInterface
{
    public function geocode(string $address)
    {
        $token = $_ENV['GOOGLE_MAPS_TOKEN'] ?? getenv('GOOGLE_MAPS_TOKEN');
        if (!is_string($token) || $token === '') {
            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            throw new \RuntimeException('Google Maps token is not configured.');
        }

        $query = http_build_query([
            'key' => $token,
            'address' => $address,
        ]);

        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
            ],
        ]);

        $response = file_get_contents(
            'https://maps.googleapis.com/maps/api/geocode/json?' . $query,
            false,
            $context
        );
        if ($response === false || $response === '') {
            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            throw new \RuntimeException('Failed to fetch geocode from Google Maps.');
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            throw new \RuntimeException('Invalid JSON response from Google Maps.');
        }

        $status = $data['status'] ?? null;
        if (!is_string($status) || $status === '') {
            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            throw new \RuntimeException('Google Maps response did not include a status.');
        }

        if ($status !== 'OK') {
            if ($status === 'ZERO_RESULTS') {
                return [
                    'lat' => null,
                    'lng' => null,
                ];
            }

            $errorMessage = '';
            if (isset($data['error_message']) && is_string($data['error_message'])) {
                $errorMessage = $data['error_message'];
            }

            $message = 'Google Maps geocode failed with status: ' . $status;
            if ($errorMessage !== '') {
                $message .= '. ' . $errorMessage;
            }

            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            throw new \RuntimeException($message);
        }

        if (!isset($data['results'][0]['geometry']['location']) || !is_array($data['results'][0]['geometry']['location'])) {
            // TO-MONITOR-0001: Future monitoring code will need to be provided in the future.
            throw new \RuntimeException('Google Maps response did not include a location.');
        }

        $location = $data['results'][0]['geometry']['location'];

        return [
            'lat' => $location['lat'] ?? null,
            'lng' => $location['lng'] ?? null,
        ];
    }
}

