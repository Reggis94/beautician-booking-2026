<?php

namespace App\ProAuth\Application\Query;

final class GetAuthenticatedProQuery
{
    public function __construct(public readonly string $sessionId)
    {
    }
}
