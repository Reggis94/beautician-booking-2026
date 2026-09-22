<?php

namespace App\Store\Infrastructure\Dao;

use App\Store\Application\Dao\BannerPackDaoInterface;
use App\Store\Application\Dto\BannerPackDto;
use App\Store\Application\Exception\BannerDirectoryNotConfiguredException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class BannerPackDao implements BannerPackDaoInterface
{
    private const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp', 'gif'];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%store.acuity_banner_packs_directory%')]
        private readonly string $bannerPacksDirectory,
        #[Autowire('%store.acuity_banner_packs_excluded_folders%')]
        private readonly array $excludedFolders,
    ) {
    }

    /**
     * @return list<BannerPackDto>
     */
    public function findAll(): array
    {
        $baseDirectory = $this->resolveBaseDirectory();
        $entries = scandir($baseDirectory) ?: [];

        $packs = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || in_array($entry, $this->excludedFolders, true)) {
                continue;
            }

            $folderPath = $baseDirectory . DIRECTORY_SEPARATOR . $entry;
            if (!is_dir($folderPath)) {
                continue;
            }

            $banners = $this->listBannerFilenames($folderPath);
            if ($banners === []) {
                continue;
            }

            $packs[] = new BannerPackDto($entry, $banners);
        }

        usort($packs, static fn (BannerPackDto $first, BannerPackDto $second): int => strnatcmp($first->id, $second->id));

        return $packs;
    }

    public function getBannerFilePath(string $packId, string $bannerFile): string
    {
        return $this->resolveBaseDirectory() . DIRECTORY_SEPARATOR . $packId . DIRECTORY_SEPARATOR . $bannerFile;
    }

    /**
     * @return list<string>
     */
    private function listBannerFilenames(string $folderPath): array
    {
        $entries = scandir($folderPath) ?: [];

        $banners = array_values(array_filter(
            $entries,
            static fn (string $filename): bool => is_file($folderPath . DIRECTORY_SEPARATOR . $filename)
                && in_array(
                    strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
                    self::IMAGE_EXTENSIONS,
                    true
                )
        ));

        usort($banners, static fn (string $first, string $second): int => strnatcmp($first, $second));

        return $banners;
    }

    private function resolveBaseDirectory(): string
    {
        if (trim($this->bannerPacksDirectory) === '') {
            throw new BannerDirectoryNotConfiguredException();
        }

        $baseDirectory = $this->projectDir . DIRECTORY_SEPARATOR . $this->bannerPacksDirectory;

        if (!is_dir($baseDirectory)) {
            throw new BannerDirectoryNotConfiguredException();
        }

        return $baseDirectory;
    }
}
