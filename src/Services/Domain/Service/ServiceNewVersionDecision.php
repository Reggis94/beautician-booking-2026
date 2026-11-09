<?php

namespace App\Services\Domain\Service;

use App\Services\Domain\Entity\Service;
use App\Services\Domain\ValueObject\ServiceContent;

/**
 * Decides whether changing a service's content needs a new version. It only decides: handlers apply the outcome.
 *
 * A service with appointment history is never overwritten: a content change creates a new version and the old one
 * stays attached to its appointments. A service without appointment history is updated in place. Changing only the
 * category is a content change too, whether it comes from a service edit, a category rename or a category deletion
 * that keeps its services.
 *
 * The service must be loaded with its appointment count, after the catalog lock.
 */
final class ServiceNewVersionDecision
{
    public static function isRequired(Service $service, ServiceContent $newContent): bool
    {
        return $service->hasAppointments() && !$service->getContent()->equals($newContent);
    }
}
