<?php

namespace App\Store\Application\Dao;

use App\Store\Application\Dto\BannerPackDto;

interface BannerPackDaoInterface
{
    /**
     * @return list<BannerPackDto>
     */
    public function findAll(): array;

    public function getBannerFilePath(string $packId, string $bannerFile): string;
}
