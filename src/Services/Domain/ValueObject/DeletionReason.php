<?php

namespace App\Services\Domain\ValueObject;

/**
 * Why a service or category version got a deleted_at.
 *
 * Deleted: the professional deleted it explicitly.
 * Replaced: an edit created a newer version, so appointments keep this one unchanged.
 */
enum DeletionReason: string
{
    case Deleted = 'deleted';
    case Replaced = 'replaced';
}
