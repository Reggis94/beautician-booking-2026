<?php

namespace App\ProfilePro\Application\Banner\File;

final readonly class BannerImageMetadata
{
    public function __construct(
        public int $orderNumber,
        public string $finalKey
    ) {
        if ($this->orderNumber < 1) {
            throw new \InvalidArgumentException('orderNumber must be greater than or equal to 1.');
        }

        if ($this->orderNumber > 20) {
            throw new \InvalidArgumentException('orderNumber must be less than or equal to 20.');
        }

        if ($this->finalKey === '') {
            throw new \InvalidArgumentException('finalKey must not be empty.');
        }
    }
}
