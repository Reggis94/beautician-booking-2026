<?php

namespace App\Services\Infrastructure\Storage;

use App\Services\Application\Port\ServiceImageStorageInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * Stores service images in the external upload storage, outside the project directory:
 *
 *     <storage root>/services/<pro-id>/<file-name>
 *     <storage root>/services/deleted/<file-name>
 *
 * One folder per professional for active files, none per service or version, and one shared folder for deleted
 * files. File names are validated against a strict pattern, so a path can never point outside these folders.
 * Files are never removed.
 */
final class LocalServiceImageStorage implements ServiceImageStorageInterface
{
    private const FILE_NAME_PATTERN = '/^[a-f0-9]{32}\.(?:jpg|png|webp)$/';
    private const DELETED_FOLDER = 'services/deleted';
    private const DIRECTORY_MODE = 0775;

    public function __construct(
        private readonly Filesystem $filesystem,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%services.image_storage_directory%')]
        private readonly string $storageDirectory
    ) {
    }

    public function store(string $sourcePath, int $proId, string $fileName): void
    {
        $target = $this->toAbsolute(self::activeFilesFolder($proId), $fileName);
        $this->filesystem->mkdir(Path::getDirectory($target), self::DIRECTORY_MODE);

        // Filesystem::copy() would overwrite an older file, so an existing target is rejected explicitly.
        if ($this->filesystem->exists($target)) {
            throw new \RuntimeException('A service image with this file name already exists.');
        }

        $this->filesystem->copy($sourcePath, $target);
    }

    public function moveToDeleted(int $proId, string $fileName): void
    {
        $this->move(
            $this->toAbsolute(self::activeFilesFolder($proId), $fileName),
            $this->toAbsolute(self::DELETED_FOLDER, $fileName)
        );
    }

    public function restore(int $proId, string $fileName): void
    {
        $this->move(
            $this->toAbsolute(self::DELETED_FOLDER, $fileName),
            $this->toAbsolute(self::activeFilesFolder($proId), $fileName)
        );
    }

    public function absolutePath(int $proId, string $fileName): ?string
    {
        if (preg_match(self::FILE_NAME_PATTERN, $fileName) !== 1) {
            return null;
        }

        $absolutePath = $this->toAbsolute(self::activeFilesFolder($proId), $fileName);

        return is_file($absolutePath) ? $absolutePath : null;
    }

    private static function activeFilesFolder(int $proId): string
    {
        return 'services/' . $proId;
    }

    private function move(string $from, string $to): void
    {
        if (!is_file($from)) {
            throw new \RuntimeException('The service image to move does not exist.');
        }

        $this->filesystem->mkdir(Path::getDirectory($to), self::DIRECTORY_MODE);
        $this->filesystem->rename($from, $to);
    }

    private function toAbsolute(string $folder, string $fileName): string
    {
        if (preg_match(self::FILE_NAME_PATTERN, $fileName) !== 1) {
            throw new \InvalidArgumentException('Invalid service image file name.');
        }

        return Path::join($this->root(), $folder, $fileName);
    }

    private function root(): string
    {
        $storageDirectory = trim($this->storageDirectory);
        if ($storageDirectory === '') {
            throw new \RuntimeException('The service image storage directory is not configured.');
        }

        return Path::makeAbsolute($storageDirectory, $this->projectDir);
    }
}
