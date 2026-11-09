<?php

namespace App\Services\Application\Service\Query;

/**
 * The professional's whole service catalog for the ProAdmin Prestations page.
 * The professional comes from the session (ADR 0019): this query has no request input.
 */
final class GetProAdminServiceCatalogQuery
{
    public function __construct(
        public readonly int $proId
    ) {
    }
}
