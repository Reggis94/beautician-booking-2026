<?php

namespace App\Tests\Availability\Service;

use App\Availability\Domain\Service\AvailabilityService;
use App\Availability\Domain\ValueObject\WeekAvailability;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

final class TestResolveExistingOverlaps extends TestCase
{
    public function testRejectsInvalidNewAvailabilityType(): void
    {
        $existing = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-01-05 00:00'),
                new \DateTimeImmutable('2026-02-22 23:59'),
                1,
                '10:00',
                '17:00'
            ),
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Should be an instance of WeekAvailability');

        AvailabilityService::resolveExistingOverlaps(['invalid'], $existing);
    }

    public function testRejectsMismatchedNewWeekRanges(): void
    {
        $new = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-01-05 00:00'),
                new \DateTimeImmutable('2026-02-22 23:59'),
                1,
                '10:00',
                '17:00'
            ),
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-03-02 00:00'),
                new \DateTimeImmutable('2026-03-15 23:59'),
                2,
                '10:00',
                '17:00'
            ),
        ];

        $existing = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-03-02 00:00'),
                new \DateTimeImmutable('2026-03-15 23:59'),
                3,
                '10:00',
                '17:00'
            ),
        ];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('All new availabilities must share the same week range.');

        AvailabilityService::resolveExistingOverlaps($new, $existing);
    }

    public function testRejectsEmptyExistingAvailabilities(): void
    {
        $new = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-03-02 00:00'),
                new \DateTimeImmutable('2026-03-15 23:59'),
                5,
                '13:00',
                '14:00'
            ),
        ];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('resolveExistingOverlaps requires at least one overlapping availability.');

        AvailabilityService::resolveExistingOverlaps($new, []);
    }

    public function testRejectsWhenExistingDoesNotOverlapNewRange(): void
    {
        $existing = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-01-05 00:00'),
                new \DateTimeImmutable('2026-02-22 23:59'),
                1,
                '10:00',
                '17:00'
            ),
        ];

        $new = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-03-02 00:00'),
                new \DateTimeImmutable('2026-03-15 23:59'),
                5,
                '13:00',
                '14:00'
            ),
        ];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('resolveExistingOverlaps requires that all existing availabilities overlap the new availability week range. This violates the repository query contract.');

        AvailabilityService::resolveExistingOverlaps($new, $existing);
    }

    public function testFullOverlapReplacesExisting(): void
    {
        $existing = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-02-02 00:00'),
                new \DateTimeImmutable('2026-02-15 23:59'),
                5,
                '10:00',
                '16:00'
            ),
        ];

        $new = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-01-26 00:00'),
                new \DateTimeImmutable('2026-02-22 23:59'),
                5,
                '13:00',
                '14:00'
            ),
        ];

        $result = AvailabilityService::resolveExistingOverlaps($new, $existing);

        $this->assertEquals($new, $result);
    }

    public function testOverlapAtStartTrimsExisting(): void
    {
        $existing = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-02-02 00:00'),
                new \DateTimeImmutable('2026-02-22 23:59'),
                4,
                '10:00',
                '16:00'
            ),
        ];

        $new = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-01-26 00:00'),
                new \DateTimeImmutable('2026-02-08 23:59'),
                5,
                '13:00',
                '14:00'
            ),
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-01-26 00:00'),
                new \DateTimeImmutable('2026-02-08 23:59'),
                6,
                '13:00',
                '14:00'
            ),
        ];

        $expected = [
            ...$new,
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-02-09 00:00'),
                new \DateTimeImmutable('2026-02-22 23:59'),
                4,
                '10:00',
                '16:00'
            ),
        ];

        $this->assertEquals(
            $expected,
            AvailabilityService::resolveExistingOverlaps($new, $existing)
        );
    }

    public function testOverlapAtEndTrimsExisting(): void
    {
        $existing = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-01-26 00:00'),
                new \DateTimeImmutable('2026-02-08 23:59'),
                4,
                '10:00',
                '16:00'
            ),
        ];

        $new = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-02-02 00:00'),
                new \DateTimeImmutable('2026-02-22 23:59'),
                5,
                '13:00',
                '14:00'
            ),
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-02-02 00:00'),
                new \DateTimeImmutable('2026-02-22 23:59'),
                6,
                '13:00',
                '14:00'
            ),
        ];

        $expected = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-01-26 00:00'),
                new \DateTimeImmutable('2026-02-01 23:59'),
                4,
                '10:00',
                '16:00'
            ),
            ...$new,
        ];

        $this->assertEquals(
            $expected,
            AvailabilityService::resolveExistingOverlaps($new, $existing)
        );
    }

    public function testOverlapInsideSplitsExisting(): void
    {
        $existing = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-01-26 00:00'),
                new \DateTimeImmutable('2026-02-22 23:59'),
                4,
                '10:00',
                '16:00'
            ),
        ];

        $new = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-02-02 00:00'),
                new \DateTimeImmutable('2026-02-08 23:59'),
                5,
                '13:00',
                '14:00'
            ),
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-02-02 00:00'),
                new \DateTimeImmutable('2026-02-08 23:59'),
                6,
                '13:00',
                '14:00'
            ),
        ];

        $expected = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-01-26 00:00'),
                new \DateTimeImmutable('2026-02-01 23:59'),
                4,
                '10:00',
                '16:00'
            ),
            ...$new,
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-02-09 00:00'),
                new \DateTimeImmutable('2026-02-22 23:59'),
                4,
                '10:00',
                '16:00'
            ),
        ];

        $this->assertEquals(
            $expected,
            AvailabilityService::resolveExistingOverlaps($new, $existing)
        );
    }

    public function testMultipleExistingOverlaps(): void
    {
        $existing = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-01-26 00:00'),
                new \DateTimeImmutable('2026-02-08 23:59'),
                4,
                '10:00',
                '16:00'
            ),
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-02-09 00:00'),
                new \DateTimeImmutable('2026-02-22 23:59'),
                4,
                '10:00',
                '19:00'
            ),
        ];

        $new = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-02-02 00:00'),
                new \DateTimeImmutable('2026-02-15 23:59'),
                5,
                '13:00',
                '14:00'
            ),
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-02-02 00:00'),
                new \DateTimeImmutable('2026-02-15 23:59'),
                6,
                '13:00',
                '14:00'
            ),
        ];

        $expected = [
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-01-26 00:00'),
                new \DateTimeImmutable('2026-02-01 23:59'),
                4,
                '10:00',
                '16:00'
            ),
            ...$new,
            new WeekAvailability(
                1,
                new \DateTimeImmutable('2026-02-16 00:00'),
                new \DateTimeImmutable('2026-02-22 23:59'),
                4,
                '10:00',
                '19:00'
            ),
        ];

        $this->assertEquals(
            $expected,
            AvailabilityService::resolveExistingOverlaps($new, $existing)
        );
    }
}
