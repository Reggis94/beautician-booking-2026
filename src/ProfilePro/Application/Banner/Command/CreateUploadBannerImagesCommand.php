<?php

namespace App\ProfilePro\Application\Banner\Command;

use App\ProfilePro\Application\Banner\Dto\CreateUploadBannerImagesDto;

final readonly class CreateUploadBannerImagesCommand
{
    public function __construct(private CreateUploadBannerImagesDto $dto)
    {
    }

    public function getDto(): CreateUploadBannerImagesDto
    {
        return $this->dto;
    }
}
