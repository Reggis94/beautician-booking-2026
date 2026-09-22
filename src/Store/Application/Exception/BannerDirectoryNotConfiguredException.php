<?php

namespace App\Store\Application\Exception;

final class BannerDirectoryNotConfiguredException extends \RuntimeException
{
    public function __construct(string $message = 'The banner template folder is not configured.')
    {
        parent::__construct($message);
    }
}
