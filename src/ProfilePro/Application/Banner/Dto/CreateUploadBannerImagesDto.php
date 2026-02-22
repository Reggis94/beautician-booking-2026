<?php

namespace App\ProfilePro\Application\Banner\Dto;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateUploadBannerImagesDto
{
    #[Assert\Positive(message: 'pro_id must be a positive integer.')]
    public readonly int $proId;

    #[Assert\Count(
        min: 1,
        minMessage: 'images must contain at least 1 file.',
        max: 20,
        maxMessage: 'images may contain at most 20 files.'
    )]
    #[Assert\All([
        new Assert\Type(type: UploadedFile::class, message: 'each item must be an uploaded file.'),
        new Assert\Image(
            message: 'each file must be a valid image.',
            maxSize: '5M',
            maxSizeMessage: 'each file must be at most 5 MB.'
        ),
    ])]
    public readonly array $images;

    #[Assert\Count(
        min: 1,
        minMessage: 'order_numbers must contain at least 1 value.',
        max: 20,
        maxMessage: 'order_numbers may contain at most 20 values.'
    )]
    #[Assert\All([
        new Assert\Type(type: 'integer', message: 'each order number must be an integer.'),
        new Assert\Range(
            min: 1,
            max: 20,
            notInRangeMessage: 'each order number must be between 1 and 20.'
        ),
    ])]
    public readonly array $orderNumbers;

    public function __construct(int $proId, array $images, array $orderNumbers)
    {
        $this->proId = $proId;
        $this->images = array_values($images);
        $this->orderNumbers = array_values($orderNumbers);
    }
}
