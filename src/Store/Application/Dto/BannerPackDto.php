<?php

namespace App\Store\Application\Dto;

final class BannerPackDto
{
    /**
     * @param list<string> $banners
     */
    public function __construct(
        public readonly string $id,
        public readonly array $banners,
    ) {
    }
}
