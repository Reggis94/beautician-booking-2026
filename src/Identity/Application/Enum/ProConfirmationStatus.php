<?php

namespace App\Identity\Application\Enum;

enum ProConfirmationStatus: string
{
    case CONFIRMED = 'confirmed';
    case NOT_CONFIRMED = 'not_confirmed';
    case PRO_NOT_FOUND = 'pro_not_found';
}
