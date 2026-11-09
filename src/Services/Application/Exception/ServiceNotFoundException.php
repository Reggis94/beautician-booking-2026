<?php

namespace App\Services\Application\Exception;

/**
 * The service does not exist, is deleted or replaced, or belongs to another professional.
 */
final class ServiceNotFoundException extends \RuntimeException
{
}
