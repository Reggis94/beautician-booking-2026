<?php

namespace App\ProfilePro\Application\Command;

use App\ProfilePro\Application\Timezone\Dto\TimezoneDto;

final readonly class UpdateTimezoneCommand
{
    public function __construct(private TimezoneDto $timezoneDto)
    {
    }

    public function getTimezoneDto(): TimezoneDto
    {
        return $this->timezoneDto;
    }
}

