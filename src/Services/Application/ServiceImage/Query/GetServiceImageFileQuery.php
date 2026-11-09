<?php

namespace App\Services\Application\ServiceImage\Query;

/**
 * Built from the route of GET /service-images/{proId}/{fileName}, whose requirements validate both values.
 */
final class GetServiceImageFileQuery
{
    public function __construct(
        public readonly int $proId,
        public readonly string $fileName
    ) {
    }
}
