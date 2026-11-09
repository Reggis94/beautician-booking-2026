<?php

namespace App\Services\Domain\ValueObject;

use App\Services\Domain\Exception\InvalidServiceImageException;

/**
 * An uploaded service image waiting to be stored.
 *
 * The MIME type must be detected from the file content by the caller, never taken from the client.
 */
final class ImageUpload
{
    public const MAX_SIZE_BYTES = 5 * 1024 * 1024;

    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private readonly string $originalName;

    public function __construct(
        private readonly string $sourcePath,
        private readonly string $mimeType,
        private readonly int $sizeBytes,
        string $originalName
    ) {
        if (!array_key_exists($mimeType, self::EXTENSIONS)) {
            throw new InvalidServiceImageException();
        }

        if ($sizeBytes <= 0 || $sizeBytes > self::MAX_SIZE_BYTES) {
            throw new InvalidServiceImageException();
        }

        if (!is_file($sourcePath)) {
            throw new InvalidServiceImageException();
        }

        $this->originalName = mb_substr(trim($originalName), 0, 255);
    }

    /**
     * @return list<string>
     */
    public static function allowedMimeTypes(): array
    {
        return array_keys(self::EXTENSIONS);
    }

    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getExtension(): string
    {
        return self::EXTENSIONS[$this->mimeType];
    }
}
