<?php

namespace App\ProAuth\Application\QueryHandler;

use App\ProAuth\Application\Dao\SessionDaoInterface;
use App\ProAuth\Application\Query\GetAuthenticatedProQuery;
use App\ProAuth\Application\Token\OpaqueToken;
use App\ProAuth\Public\AuthenticatedPro;

final class GetAuthenticatedProQueryHandler
{
    public function __construct(private readonly SessionDaoInterface $sessionDao)
    {
    }

    /**
     * Returns null for a missing, revoked or unknown session, or when the beautician is deleted.
     */
    public function __invoke(GetAuthenticatedProQuery $query): ?AuthenticatedPro
    {
        $pro = $this->sessionDao->findActiveProBySessionHash(OpaqueToken::hash($query->sessionId));

        if ($pro === null) {
            return null;
        }

        return new AuthenticatedPro(
            $pro['id'],
            $pro['email'],
            $pro['link_slug'],
            $pro['display_name'],
            $pro['firstname'],
            $pro['lastname']
        );
    }
}
