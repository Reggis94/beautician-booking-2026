<?php

namespace App\Tests\Services\Application\Service\CommandHandler;

use App\Services\Application\Exception\ServiceCategoryNotFoundException;
use App\Services\Application\Exception\ServiceNotFoundException;
use App\Services\Application\ImageFile\ServiceImageFiles;
use App\Services\Application\Port\ServiceImageStorageInterface;
use App\Services\Application\Service\Command\UpdateServiceCommand;
use App\Services\Application\Service\CommandHandler\UpdateServiceCommandHandler;
use App\Services\Infrastructure\Repository\ServiceCategoryRepository;
use App\Services\Infrastructure\Repository\ServiceRepository;
use App\Tests\DatabaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;

/**
 * When editing a service creates a new version, through the real repositories on the test database.
 *
 * Every edit sends the whole form, like the Prestations page: the current values of the service, except the ones a
 * test changes. Images are not versioned and are not covered here.
 */
final class UpdateServiceCommandHandlerVersioningTest extends DatabaseTestCase
{
    private UpdateServiceCommandHandler $handler;
    private int $proId;
    private int $categoryId;
    private int $otherCategoryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new UpdateServiceCommandHandler(
            new ServiceRepository($this->connection),
            new ServiceCategoryRepository($this->connection),
            new ServiceImageFiles($this->createStub(ServiceImageStorageInterface::class), new NullLogger())
        );
        $this->proId = $this->insertPro('jane.doe@example.com', 'jane-doe');
        $this->categoryId = $this->insertCategory($this->proId, 'Soins');
        $this->otherCategoryId = $this->insertCategory($this->proId, 'Massages');
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function contentChanges(): iterable
    {
        yield 'name' => [['name' => 'Soin du visage']];
        yield 'description changed' => [['description' => 'Un autre soin']];
        yield 'description removed' => [['description' => null]];
        yield 'duration' => [['durationMin' => 45]];
        yield 'price' => [['priceCents' => 3000]];
        yield 'price set to free' => [['priceCents' => 0]];
        yield 'every field' => [[
            'name' => 'Soin du visage',
            'description' => 'Un autre soin',
            'durationMin' => 45,
            'priceCents' => 3000,
        ]];
    }

    /**
     * @param array<string, mixed> $changes
     */
    #[Test]
    #[DataProvider('contentChanges')]
    public function a_content_change_on_a_booked_service_creates_a_new_version(array $changes): void
    {
        $serviceId = $this->insertService();
        $appointmentId = $this->insertAppointment($serviceId);
        $contentBefore = $this->contentOf($this->serviceRow($serviceId));

        $this->edit($serviceId, $changes);

        $this->assertReplaced($serviceId, $contentBefore, array_merge($contentBefore, $changes));
        $this->assertAppointmentStaysOn($appointmentId, $serviceId);
    }

    /**
     * @param array<string, mixed> $changes
     */
    #[Test]
    #[DataProvider('contentChanges')]
    public function a_content_change_on_a_service_without_appointments_updates_it_in_place(array $changes): void
    {
        $serviceId = $this->insertService();
        $contentBefore = $this->contentOf($this->serviceRow($serviceId));

        $this->edit($serviceId, $changes);

        $this->assertUpdatedInPlace($serviceId, array_merge($contentBefore, $changes));
    }

    #[Test]
    public function adding_a_description_to_a_booked_service_creates_a_new_version(): void
    {
        $serviceId = $this->insertService(description: null);
        $this->insertAppointment($serviceId);
        $contentBefore = $this->contentOf($this->serviceRow($serviceId));

        $this->edit($serviceId, ['description' => 'Un soin complet']);

        $this->assertReplaced(
            $serviceId,
            $contentBefore,
            array_merge($contentBefore, ['description' => 'Un soin complet'])
        );
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function categoryChanges(): iterable
    {
        yield 'moved to another category' => ['current', 'other'];
        yield 'removed from its category' => ['current', null];
        yield 'added to a category' => [null, 'current'];
    }

    #[Test]
    #[DataProvider('categoryChanges')]
    public function a_category_change_on_a_booked_service_creates_a_new_version(?string $from, ?string $to): void
    {
        $serviceId = $this->insertService(categoryId: $this->categoryIdFor($from));
        $appointmentId = $this->insertAppointment($serviceId);
        $contentBefore = $this->contentOf($this->serviceRow($serviceId));
        $newCategoryId = $this->categoryIdFor($to);

        $this->edit($serviceId, ['categoryId' => $newCategoryId]);

        $this->assertReplaced(
            $serviceId,
            $contentBefore,
            array_merge($contentBefore, ['categoryId' => $newCategoryId])
        );
        $this->assertAppointmentStaysOn($appointmentId, $serviceId);
    }

    #[Test]
    #[DataProvider('categoryChanges')]
    public function a_category_change_on_a_service_without_appointments_updates_it_in_place(
        ?string $from,
        ?string $to
    ): void {
        $serviceId = $this->insertService(categoryId: $this->categoryIdFor($from));
        $contentBefore = $this->contentOf($this->serviceRow($serviceId));
        $newCategoryId = $this->categoryIdFor($to);

        $this->edit($serviceId, ['categoryId' => $newCategoryId]);

        $this->assertUpdatedInPlace($serviceId, array_merge($contentBefore, ['categoryId' => $newCategoryId]));
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function appointmentsOfAnyKind(): iterable
    {
        yield 'past' => ['2000-06-01 09:00:00', null];
        yield 'future' => ['2099-06-01 09:00:00', null];
        yield 'soft-deleted' => ['2099-06-01 09:00:00', '2026-01-01 09:00:00+00'];
    }

    #[Test]
    #[DataProvider('appointmentsOfAnyKind')]
    public function every_kind_of_appointment_makes_a_content_change_create_a_new_version(
        string $startDt,
        ?string $deletedAt
    ): void {
        $serviceId = $this->insertService();
        $appointmentId = $this->insertAppointment($serviceId, $startDt, $deletedAt);
        $contentBefore = $this->contentOf($this->serviceRow($serviceId));

        $this->edit($serviceId, ['name' => 'Soin du visage']);

        $this->assertReplaced($serviceId, $contentBefore, array_merge($contentBefore, ['name' => 'Soin du visage']));
        $this->assertAppointmentStaysOn($appointmentId, $serviceId);
    }

    #[Test]
    public function an_appointment_on_another_service_does_not_create_a_new_version(): void
    {
        $serviceId = $this->insertService();
        $bookedSiblingId = $this->insertService(name: 'Soin corps');
        $this->insertAppointment($bookedSiblingId);
        $contentBefore = $this->contentOf($this->serviceRow($serviceId));
        $siblingBefore = $this->serviceRow($bookedSiblingId);

        $this->edit($serviceId, ['name' => 'Soin du visage']);

        $this->assertUpdatedInPlaceWithoutOtherRows(
            $serviceId,
            array_merge($contentBefore, ['name' => 'Soin du visage']),
            2
        );
        self::assertSame($siblingBefore, $this->serviceRow($bookedSiblingId));
    }

    #[Test]
    public function resending_the_same_content_on_a_booked_service_changes_nothing(): void
    {
        $serviceId = $this->insertService();
        $this->insertAppointment($serviceId);
        $before = $this->serviceRow($serviceId);

        $this->edit($serviceId, []);

        self::assertSame($before, $this->serviceRow($serviceId));
        self::assertSame(1, $this->serviceCount());
    }

    /**
     * @return iterable<string, array{?string, array<string, mixed>}>
     */
    public static function valuesEqualOnceNormalized(): iterable
    {
        yield 'name with surrounding spaces' => ['Un soin complet', ['name' => '  Soin visage  ']];
        yield 'description with surrounding spaces' => ['Un soin complet', ['description' => '  Un soin complet  ']];
        yield 'blank description on a service without one' => [null, ['description' => '   ']];
    }

    /**
     * @param array<string, mixed> $changes
     */
    #[Test]
    #[DataProvider('valuesEqualOnceNormalized')]
    public function values_equal_once_normalized_do_not_create_a_new_version(
        ?string $description,
        array $changes
    ): void {
        $serviceId = $this->insertService(description: $description);
        $this->insertAppointment($serviceId);
        $contentBefore = $this->contentOf($this->serviceRow($serviceId));

        $this->edit($serviceId, $changes);

        $this->assertUpdatedInPlace($serviceId, $contentBefore);
    }

    /**
     * @return iterable<string, array{bool, ?string, bool}>
     */
    public static function publicationOnlyEdits(): iterable
    {
        foreach (['booked' => true, 'without appointments' => false] as $label => $isBooked) {
            yield "publishing a draft, {$label}" => [$isBooked, null, true];
            yield "unpublishing, {$label}" => [$isBooked, '2026-01-15 10:00:00+00', false];
            yield "staying published, {$label}" => [$isBooked, '2026-01-15 10:00:00+00', true];
            yield "staying a draft, {$label}" => [$isBooked, null, false];
        }
    }

    #[Test]
    #[DataProvider('publicationOnlyEdits')]
    public function changing_only_the_publication_state_creates_no_new_version(
        bool $isBooked,
        ?string $initialPublishedAt,
        bool $published
    ): void {
        $serviceId = $this->insertService(publishedAt: $initialPublishedAt);
        if ($isBooked) {
            $this->insertAppointment($serviceId);
        }
        $before = $this->serviceRow($serviceId);

        $this->edit($serviceId, ['published' => $published]);

        $this->assertUpdatedInPlace($serviceId, $this->contentOf($before));

        $publishedAt = $this->serviceRow($serviceId)['published_at'];
        if (!$published) {
            self::assertNull($publishedAt);
        } elseif ($initialPublishedAt === null) {
            self::assertNotNull($publishedAt);
        } else {
            self::assertSame($before['published_at'], $publishedAt);
        }
    }

    #[Test]
    public function a_new_version_of_a_published_service_keeps_its_publication_date(): void
    {
        $serviceId = $this->insertService(publishedAt: '2026-01-15 10:00:00+00');
        $this->insertAppointment($serviceId);
        $publishedAt = $this->serviceRow($serviceId)['published_at'];

        $this->edit($serviceId, ['name' => 'Soin du visage']);

        self::assertSame($publishedAt, $this->serviceRow($serviceId)['published_at']);
        self::assertSame($publishedAt, $this->newVersionOf($serviceId)['published_at']);
    }

    #[Test]
    public function publishing_while_changing_content_only_publishes_the_new_version(): void
    {
        $serviceId = $this->insertService(publishedAt: null);
        $this->insertAppointment($serviceId);

        $this->edit($serviceId, ['name' => 'Soin du visage', 'published' => true]);

        self::assertNull($this->serviceRow($serviceId)['published_at']);
        self::assertNotNull($this->newVersionOf($serviceId)['published_at']);
    }

    #[Test]
    public function unpublishing_while_changing_content_only_unpublishes_the_new_version(): void
    {
        $serviceId = $this->insertService(publishedAt: '2026-01-15 10:00:00+00');
        $this->insertAppointment($serviceId);
        $publishedAt = $this->serviceRow($serviceId)['published_at'];

        $this->edit($serviceId, ['name' => 'Soin du visage', 'published' => false]);

        self::assertSame($publishedAt, $this->serviceRow($serviceId)['published_at']);
        self::assertNull($this->newVersionOf($serviceId)['published_at']);
    }

    #[Test]
    public function a_new_version_without_appointments_is_then_updated_in_place(): void
    {
        $firstVersionId = $this->insertService();
        $this->insertAppointment($firstVersionId);
        $this->edit($firstVersionId, ['name' => 'Soin du visage']);
        $secondVersionId = (int) $this->newVersionOf($firstVersionId)['id'];

        $this->edit($secondVersionId, ['name' => 'Soin du visage complet']);

        self::assertNull($this->findNewVersionOf($secondVersionId));
        self::assertSame('Soin du visage complet', $this->serviceRow($secondVersionId)['name']);
        self::assertSame('Soin visage', $this->serviceRow($firstVersionId)['name']);
        self::assertSame(2, $this->serviceCount());
    }

    #[Test]
    public function a_new_version_with_its_own_appointment_is_replaced_in_turn(): void
    {
        $firstVersionId = $this->insertService();
        $firstAppointmentId = $this->insertAppointment($firstVersionId);
        $this->edit($firstVersionId, ['name' => 'Soin du visage']);
        $secondVersionId = (int) $this->newVersionOf($firstVersionId)['id'];
        $secondAppointmentId = $this->insertAppointment($secondVersionId);

        $this->edit($secondVersionId, ['name' => 'Soin du visage complet']);

        $thirdVersion = $this->newVersionOf($secondVersionId);
        self::assertSame('Soin du visage complet', $thirdVersion['name']);
        self::assertNull($thirdVersion['deleted_at']);
        self::assertSame('Soin visage', $this->serviceRow($firstVersionId)['name']);
        self::assertSame('Soin du visage', $this->serviceRow($secondVersionId)['name']);
        self::assertSame('replaced', $this->serviceRow($secondVersionId)['deletion_reason']);
        self::assertSame(3, $this->serviceCount());
        $this->assertAppointmentStaysOn($firstAppointmentId, $firstVersionId);
        $this->assertAppointmentStaysOn($secondAppointmentId, $secondVersionId);
    }

    #[Test]
    public function a_replaced_version_cannot_be_edited(): void
    {
        $serviceId = $this->insertService();
        $this->insertAppointment($serviceId);
        $this->edit($serviceId, ['name' => 'Soin du visage']);

        $this->assertEditRefused(ServiceNotFoundException::class, $serviceId, ['name' => 'Autre nom']);
    }

    #[Test]
    public function an_explicitly_deleted_service_cannot_be_edited(): void
    {
        $serviceId = $this->insertService();
        $this->connection->executeStatement(
            "UPDATE service SET deleted_at = NOW(), deletion_reason = 'deleted' WHERE id = ?",
            [$serviceId]
        );

        $this->assertEditRefused(ServiceNotFoundException::class, $serviceId, ['name' => 'Autre nom']);
    }

    #[Test]
    public function the_service_of_another_professional_cannot_be_edited(): void
    {
        $otherProId = $this->insertPro('other.pro@example.com', 'other-pro');
        $serviceId = $this->insertService(categoryId: null, proId: $otherProId);
        $this->insertAppointment($serviceId, proId: $otherProId);

        $this->assertEditRefused(ServiceNotFoundException::class, $serviceId, ['name' => 'Autre nom']);
    }

    #[Test]
    public function a_refused_category_creates_no_new_version(): void
    {
        $serviceId = $this->insertService();
        $this->insertAppointment($serviceId);
        $deletedCategoryId = $this->insertCategory($this->proId, 'Ancienne');
        $this->connection->executeStatement(
            "UPDATE service_category SET deleted_at = NOW(), deletion_reason = 'deleted' WHERE id = ?",
            [$deletedCategoryId]
        );
        $otherProCategoryId = $this->insertCategory(
            $this->insertPro('other.pro@example.com', 'other-pro'),
            'Autre pro'
        );

        foreach ([$deletedCategoryId, $otherProCategoryId] as $refusedCategoryId) {
            $this->assertEditRefused(
                ServiceCategoryNotFoundException::class,
                $serviceId,
                ['name' => 'Autre nom', 'categoryId' => $refusedCategoryId]
            );
        }
    }

    /**
     * The old row keeps its content and is retired; a single new row holds the new content and references it.
     *
     * @param array<string, mixed> $oldContent
     * @param array<string, mixed> $newContent
     */
    private function assertReplaced(int $serviceId, array $oldContent, array $newContent): void
    {
        $old = $this->serviceRow($serviceId);
        self::assertSame($oldContent, $this->contentOf($old));
        self::assertNotNull($old['deleted_at']);
        self::assertSame('replaced', $old['deletion_reason']);

        $newVersion = $this->newVersionOf($serviceId);
        self::assertSame($newContent, $this->contentOf($newVersion));
        self::assertNull($newVersion['deleted_at']);
        self::assertNull($newVersion['deletion_reason']);
        self::assertSame((int) $old['pro_id'], (int) $newVersion['pro_id']);

        self::assertSame(2, $this->serviceCount());
    }

    /**
     * @param array<string, mixed> $content
     */
    private function assertUpdatedInPlace(int $serviceId, array $content): void
    {
        $this->assertUpdatedInPlaceWithoutOtherRows($serviceId, $content, 1);
    }

    /**
     * The same row is still in use with this content, no new row references it and no row was added.
     *
     * @param array<string, mixed> $content
     */
    private function assertUpdatedInPlaceWithoutOtherRows(int $serviceId, array $content, int $expectedRowCount): void
    {
        $row = $this->serviceRow($serviceId);
        self::assertSame($content, $this->contentOf($row));
        self::assertNull($row['deleted_at']);
        self::assertNull($row['deletion_reason']);
        self::assertNull($this->findNewVersionOf($serviceId));
        self::assertSame($expectedRowCount, $this->serviceCount());
    }

    private function assertAppointmentStaysOn(int $appointmentId, int $serviceId): void
    {
        self::assertSame(
            $serviceId,
            (int) $this->connection->fetchOne('SELECT service_id FROM appointment WHERE id = ?', [$appointmentId])
        );
    }

    /**
     * The edit fails with this exception and leaves every service row as it was.
     *
     * @param class-string<\Throwable> $exceptionClass
     * @param array<string, mixed>     $changes
     */
    private function assertEditRefused(string $exceptionClass, int $serviceId, array $changes): void
    {
        $rowsBefore = $this->allServiceRows();

        try {
            $this->edit($serviceId, $changes);
            self::fail('The edit should have been refused.');
        } catch (\Throwable $exception) {
            self::assertInstanceOf($exceptionClass, $exception);
        }

        self::assertSame($rowsBefore, $this->allServiceRows());
    }

    /**
     * Sends the whole form: the current values of the service, except the changed ones.
     *
     * @param array<string, mixed> $changes
     */
    private function edit(int $serviceId, array $changes): void
    {
        $row = $this->serviceRow($serviceId);
        $form = array_merge(
            ['serviceId' => $serviceId],
            $this->contentOf($row),
            ['published' => $row['published_at'] !== null],
            $changes
        );

        // Editing the service of another professional is done as the professional of this test.
        ($this->handler)(new UpdateServiceCommand(...$form), $this->proId, []);
    }

    /**
     * The values that make up the content of a version, named like the command arguments.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function contentOf(array $row): array
    {
        return [
            'name' => $row['name'],
            'description' => $row['description'],
            'durationMin' => (int) $row['duration_min'],
            'priceCents' => (int) $row['price_cents'],
            'categoryId' => $row['category_id'] !== null ? (int) $row['category_id'] : null,
        ];
    }

    private function categoryIdFor(?string $category): ?int
    {
        return match ($category) {
            'current' => $this->categoryId,
            'other' => $this->otherCategoryId,
            null => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceRow(int $serviceId): array
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM service WHERE id = ?', [$serviceId]);
        self::assertIsArray($row);

        return $row;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function allServiceRows(): array
    {
        return $this->connection->fetchAllAssociative('SELECT * FROM service ORDER BY id');
    }

    private function serviceCount(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM service');
    }

    /**
     * @return array<string, mixed>
     */
    private function newVersionOf(int $serviceId): array
    {
        $row = $this->findNewVersionOf($serviceId);
        self::assertNotNull($row);

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findNewVersionOf(int $serviceId): ?array
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM service WHERE previous_version_id = ?', [$serviceId]);

        return $row === false ? null : $row;
    }

    private function insertPro(string $email, string $linkSlug): int
    {
        return (int) $this->connection->fetchOne(
            'INSERT INTO pro (email, link_slug) VALUES (?, ?) RETURNING id',
            [$email, $linkSlug]
        );
    }

    private function insertCategory(int $proId, string $name): int
    {
        return (int) $this->connection->fetchOne(
            'INSERT INTO service_category (pro_id, name) VALUES (?, ?) RETURNING id',
            [$proId, $name]
        );
    }

    /**
     * A published service in the first category by default, for 30 minutes and 25 euros.
     */
    private function insertService(
        string $name = 'Soin visage',
        ?string $description = 'Un soin complet',
        ?int $categoryId = -1,
        ?string $publishedAt = '2026-01-15 10:00:00+00',
        ?int $proId = null
    ): int {
        return (int) $this->connection->fetchOne(
            'INSERT INTO service (pro_id, category_id, name, description, duration_min, price_cents, published_at)
             VALUES (?, ?, ?, ?, 30, 2500, ?)
             RETURNING id',
            [
                $proId ?? $this->proId,
                $categoryId === -1 ? $this->categoryId : $categoryId,
                $name,
                $description,
                $publishedAt,
            ]
        );
    }

    private function insertAppointment(
        int $serviceId,
        string $startDt = '2099-06-01 09:00:00',
        ?string $deletedAt = null,
        ?int $proId = null
    ): int {
        return (int) $this->connection->fetchOne(
            'INSERT INTO appointment (pro_id, service_id, start_dt, deleted_at) VALUES (?, ?, ?, ?) RETURNING id',
            [$proId ?? $this->proId, $serviceId, $startDt, $deletedAt]
        );
    }
}
