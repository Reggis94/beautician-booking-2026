<?php

namespace App\Services\Application\Exception;

/**
 * The service category does not exist, is deleted or replaced, or belongs to another professional.
 */
final class ServiceCategoryNotFoundException extends \RuntimeException
{
}
