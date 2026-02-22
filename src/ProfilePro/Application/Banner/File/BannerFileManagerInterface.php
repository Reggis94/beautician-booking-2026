<?php

namespace App\ProfilePro\Application\Banner\File;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface BannerFileManagerInterface
{
    /**
     * @param array<int, array{file: UploadedFile, orderNumber: int}> $files
     */
    public function stageAll(array $files, int $proId, string $commitId): void;

    /**
     * @return BannerImageMetadata[]
     */
    public function getStagedMetadata(): array;

    public function publishStaged(): void;
}
