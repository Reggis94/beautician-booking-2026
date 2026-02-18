<?php

namespace App\ProfilePro\Infrastructure\File;

use App\ProfilePro\Application\Banner\File\BannerImageMetadata;
use App\ProfilePro\Application\Banner\File\BannerFileManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class BannerFileManager implements BannerFileManagerInterface
{
    private const DIRECTORY_PERMISSIONS = 0775;

    /**
     * @var array<int, array{stagedPath: string, metadata: BannerImageMetadata}>
     */
    private array $stagedFiles = [];

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir
    ) {
    }

    public function upload(UploadedFile $file, string $key): void
    {
        $targetPath = $this->resolvePathFromKey($key);
        $targetDirectory = dirname($targetPath);
        if (!is_string($targetDirectory) || $targetDirectory === '') {
            throw new \RuntimeException('Unable to resolve target directory for banner upload.');
        }

        $this->ensureDirectoryExists($targetDirectory);

        $targetFilename = basename($targetPath);
        try {
            $file->move($targetDirectory, $targetFilename);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Unable to upload banner file.', 0, $exception);
        }
    }

    public function stageAll(array $files, int $proId, string $commitId): void
    {
        $this->stagedFiles = [];

        $stagingProDirectory = $this->getStagingProDirectory($proId);
        $this->ensureDirectoryExists($stagingProDirectory);

        $stagingDirectory = $this->getStagingDirectory($proId, $commitId);
        $this->ensureDirectoryExists($stagingDirectory);

        try {
            foreach ($files as $fileEntry) {
                if (!is_array($fileEntry)) {
                    throw new \InvalidArgumentException('each file entry must be an array.');
                }

                $file = $fileEntry['file'] ?? null;
                $orderNumber = $fileEntry['orderNumber'] ?? null;
                if (!$file instanceof UploadedFile) {
                    throw new \InvalidArgumentException('each item must be an uploaded file.');
                }
                if (!is_int($orderNumber)) {
                    throw new \InvalidArgumentException('each order number must be an integer.');
                }

                $sourcePath = $file->getPathname();
                if ($sourcePath === '') {
                    throw new \RuntimeException('Unable to resolve source path for banner staging.');
                }

                $extension = $this->resolveExtension($file);
                $finalKey = $this->buildFinalKey($proId, $commitId, $extension);
                $stagedFilename = basename($finalKey);
                $stagedPath = $stagingDirectory . '/' . $stagedFilename;

                $this->copyFile($sourcePath, $stagedPath);

                $this->stagedFiles[] = [
                    'stagedPath' => $stagedPath,
                    'metadata' => new BannerImageMetadata($orderNumber, $finalKey),
                ];
            }
        } catch (\Throwable $exception) {
            $this->stagedFiles = [];
            throw $exception;
        }
    }

    public function getStagedMetadata(): array
    {
        $metadata = [];
        foreach ($this->stagedFiles as $stagedFile) {
            $metadata[] = $stagedFile['metadata'];
        }

        return $metadata;
    }

    public function publishStaged(): void
    {
        if ($this->stagedFiles === []) {
            return;
        }

        $firstPathParts = $this->extractPathPartsFromFinalKey($this->stagedFiles[0]['metadata']->finalKey);
        $proIdPath = $firstPathParts['proIdPath'];
        $commitIdPath = $firstPathParts['commitIdPath'];
        $publishingCommitIdPath = 'publishing-' . $commitIdPath;
        $publishingDirectory = $this->getBaseDirectory() . '/' . $proIdPath . '/' . $publishingCommitIdPath;
        $finalDirectory = $this->getBaseDirectory() . '/' . $proIdPath . '/' . $commitIdPath;
        $proDirectory = $this->getBaseDirectory() . '/' . $proIdPath;

        $this->ensureDirectoryExists($proDirectory);

        if (is_dir($finalDirectory)) {
            throw new \RuntimeException('Unable to publish staged banners: final directory already exists.');
        }

        foreach ($this->stagedFiles as $stagedFile) {
            $pathParts = $this->extractPathPartsFromFinalKey($stagedFile['metadata']->finalKey);
            if ($pathParts['proIdPath'] !== $proIdPath || $pathParts['commitIdPath'] !== $commitIdPath) {
                throw new \RuntimeException('Unable to publish staged banners: inconsistent commit directory.');
            }

            $stagedPath = $stagedFile['stagedPath'];
            $targetPath = $this->resolvePathFromKey(
                $proIdPath . '/' . $publishingCommitIdPath . '/' . $pathParts['filename']
            );
            $targetDirectory = dirname($targetPath);
            if (!is_string($targetDirectory) || $targetDirectory === '') {
                throw new \RuntimeException('Unable to resolve target directory for staged banner publish.');
            }

            $this->ensureDirectoryExists($targetDirectory);
            $this->moveFile($stagedPath, $targetPath);
        }

        $finalParentDirectory = dirname($finalDirectory);
        if (!is_string($finalParentDirectory) || $finalParentDirectory === '') {
            throw new \RuntimeException('Unable to resolve final directory for staged banner publish.');
        }

        $this->ensureDirectoryExists($finalParentDirectory);
        if (!@rename($publishingDirectory, $finalDirectory) || !is_dir($finalDirectory)) {
            throw new \RuntimeException('Unable to finalize publishing banner directory.');
        }

        $this->stagedFiles = [];
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!@mkdir($directory, self::DIRECTORY_PERMISSIONS, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create banner directory.');
        }
    }

    private function resolvePathFromKey(string $key): string
    {
        $normalizedKey = str_replace('\\', '/', trim($key));
        $normalizedKey = ltrim($normalizedKey, '/');

        if ($normalizedKey === '' || str_contains($normalizedKey, '..')) {
            throw new \InvalidArgumentException('Invalid banner file key.');
        }

        return $this->getBaseDirectory() . '/' . $normalizedKey;
    }

    private function getBaseDirectory(): string
    {
        return $this->projectDir . '/public/pro-banners';
    }

    private function getStagingDirectory(int $proId, string $commitId): string
    {
        $normalizedCommitId = $this->normalizeCommitId($commitId);
        return $this->getBaseDirectory() . '/.staging/' . $proId . '/' . $normalizedCommitId;
    }

    private function getStagingProDirectory(int $proId): string
    {
        return $this->getBaseDirectory() . '/.staging/' . $proId;
    }

    private function resolveExtension(UploadedFile $file): string
    {
        $extension = $file->guessExtension();
        if (!is_string($extension) || $extension === '') {
            $originalExtension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $extension = is_string($originalExtension) && $originalExtension !== ''
                ? $originalExtension
                : 'bin';
        }

        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $extension) ?: 'bin');
    }

    private function buildFinalKey(int $proId, string $commitId, string $extension): string
    {
        return $proId . '/' . $commitId . '/' . bin2hex(random_bytes(16)) . '.' . $extension;
    }

    private function copyFile(string $sourcePath, string $targetPath): void
    {
        if (!@copy($sourcePath, $targetPath) || !is_file($targetPath)) {
            throw new \RuntimeException('Unable to stage banner file.');
        }
    }

    private function moveFile(string $sourcePath, string $targetPath): void
    {
        if (@rename($sourcePath, $targetPath) && is_file($targetPath)) {
            return;
        }

        $this->copyFile($sourcePath, $targetPath);

        if (!@unlink($sourcePath) && file_exists($sourcePath)) {
            throw new \RuntimeException('Unable to finalize staged banner file.');
        }
    }

    private function normalizeCommitId(string $commitId): string
    {
        $normalizedCommitId = preg_replace('/[^a-zA-Z0-9]/', '', $commitId);
        if (!is_string($normalizedCommitId) || $normalizedCommitId === '') {
            throw new \InvalidArgumentException('Invalid banner commit id.');
        }

        return $normalizedCommitId;
    }

    /**
     * @return array{proIdPath: string, commitIdPath: string, filename: string}
     */
    private function extractPathPartsFromFinalKey(string $finalKey): array
    {
        $normalizedKey = str_replace('\\', '/', trim($finalKey));
        $normalizedKey = ltrim($normalizedKey, '/');
        $parts = explode('/', $normalizedKey);
        if (count($parts) !== 3) {
            throw new \RuntimeException('Unable to publish staged banners: invalid final key format.');
        }

        $proIdPath = $parts[0];
        $commitIdPath = $parts[1];
        $filename = $parts[2];
        if ($proIdPath === '' || $commitIdPath === '' || $filename === '') {
            throw new \RuntimeException('Unable to publish staged banners: invalid final key segments.');
        }
        if (str_contains($proIdPath, '..') || str_contains($commitIdPath, '..') || str_contains($filename, '..')) {
            throw new \RuntimeException('Unable to publish staged banners: unsafe final key segments.');
        }

        return [
            'proIdPath' => $proIdPath,
            'commitIdPath' => $commitIdPath,
            'filename' => $filename,
        ];
    }
}
