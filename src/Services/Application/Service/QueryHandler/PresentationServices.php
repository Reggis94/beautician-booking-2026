<?php

namespace App\Services\Application\Service\QueryHandler;

use App\Services\Domain\Repository\ServiceRepositoryInterface;
use App\Services\Domain\Service\FrenchNameOrder;

/**
 * Shared shaping of public presentation service lists: French alphabetical order and active images.
 */
final class PresentationServices
{
    /**
     * @param list<array{id: int, name: string, description: ?string, price_cents: ?int, duration_min: ?int}> $rows
     *
     * @return list<array{
     *     id: int,
     *     name: string,
     *     description: ?string,
     *     price_cents: ?int,
     *     duration_min: ?int,
     *     images: list<array{id: int, file_name: string}>
     * }>
     */
    public static function withImagesInOrder(ServiceRepositoryInterface $services, array $rows): array
    {
        usort($rows, static function (array $first, array $second): int {
            return FrenchNameOrder::compareWithId($first['name'], $first['id'], $second['name'], $second['id']);
        });

        $serviceIds = [];
        foreach ($rows as $row) {
            $serviceIds[] = $row['id'];
        }
        $imagesByService = $services->listActiveImages($serviceIds);

        $result = [];
        foreach ($rows as $row) {
            $row['images'] = $imagesByService[$row['id']] ?? [];
            $result[] = $row;
        }

        return $result;
    }
}
