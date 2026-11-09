<?php

namespace App\Services\Domain\Exception;

/**
 * A service cannot have more than two active images.
 */
final class ServiceImageLimitExceededException extends \DomainException
{
}
