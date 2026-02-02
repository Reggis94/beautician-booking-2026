<?php

namespace App\Tests\Availability\Domain;

use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

final class TestAvailabilityTimezoneDeadline extends TestCase
{
    public function testAvailabilityTimezoneDeadline(): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $deadline = new \DateTimeImmutable('2026-02-15 00:00', new \DateTimeZone('UTC'));

        if ($now >= $deadline) {
            $this->fail('DST-safe, multi-timezone, and non-UK logic must be implemented before 2026-03-29.');
        }

        $this->assertTrue(true);
    }
}
