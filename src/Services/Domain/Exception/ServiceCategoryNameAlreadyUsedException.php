<?php

namespace App\Services\Domain\Exception;

/**
 * Another not deleted category of this professional already uses this name.
 */
final class ServiceCategoryNameAlreadyUsedException extends \DomainException
{
}
