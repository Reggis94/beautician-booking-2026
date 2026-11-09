<?php

namespace App\Services\Application\ServiceImage\QueryHandler;

use App\Services\Application\Port\ServiceImageStorageInterface;
use App\Services\Application\ServiceImage\Query\GetServiceImageFileQuery;
use App\Services\Domain\Repository\ServiceRepositoryInterface;

/**
 * Resolves a served service image. Only active images of the professional are served: removed images
 * (moved to the deleted folder) and arbitrary paths are not reachable.
 */
final class GetServiceImageFileQueryHandler
{
    private const MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    public function __construct(
        private readonly ServiceRepositoryInterface $services,
        private readonly ServiceImageStorageInterface $storage
    ) {
    }

    /**
     * @return array{path: string, mime_type: string}|null
     */
    public function __invoke(GetServiceImageFileQuery $query): ?array
    {
        if (!$this->services->isActiveImage($query->proId, $query->fileName)) {
            return null;
        }

        $path = $this->storage->absolutePath($query->proId, $query->fileName);
        $mimeType = self::MIME_TYPES[pathinfo($query->fileName, PATHINFO_EXTENSION)] ?? null;
        if ($path === null || $mimeType === null) {
            return null;
        }

        return [
            'mime_type' => $mimeType,
            'path' => $path,
        ];
    }
}
