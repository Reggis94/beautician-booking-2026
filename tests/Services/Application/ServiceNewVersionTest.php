<?php

namespace App\Tests\Services\Application;

use App\Services\Application\ImageFile\ServiceImageFiles;
use App\Services\Application\Port\ServiceImageStorageInterface;
use App\Services\Application\Service\Command\UpdateServiceCommand;
use App\Services\Application\Service\CommandHandler\UpdateServiceCommandHandler;
use App\Services\Application\ServiceCategory\Command\DeleteServiceCategoryCommand;
use App\Services\Application\ServiceCategory\Command\RenameServiceCategoryCommand;
use App\Services\Application\ServiceCategory\CommandHandler\DeleteServiceCategoryCommandHandler;
use App\Services\Application\ServiceCategory\CommandHandler\RenameServiceCategoryCommandHandler;
use App\Services\Infrastructure\Repository\ServiceCategoryRepository;
use App\Services\Infrastructure\Repository\ServiceRepository;
use App\Tests\DatabaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;

/**
 * When a service gets a new version, through the real repositories on the test database.
 *
 * A content change creates a new version for a service with appointments and updates in place one without any.
 */
final class ServiceNewVersionTest extends DatabaseTestCase
{
    private ServiceRepository $services;
    private ServiceCategoryRepository $categories;
    private int $proId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->services = new ServiceRepository($this->connection);
        $this->categories = new ServiceCategoryRepository($this->connection);
        $this->proId = (int) $this->connection->fetchOne(
            "INSERT INTO pro (email, link_slug) VALUES ('jane.doe@example.com', 'jane-doe') RETURNING id"
        );
    }

    #[Test]
    public function renaming_a_booked_category_creates_a_new_version_only_for_booked_services(): void
    {
        $categoryId = $this->insertCategory('Soins');
        $bookedId = $this->insertService($categoryId, 'Soin visage');
        $notBookedId = $this->insertService($categoryId, 'Soin corps');
        $this->insertAppointment($bookedId);

        $handler = new RenameServiceCategoryCommandHandler($this->services, $this->categories);
        $handler(new RenameServiceCategoryCommand($categoryId, 'Beaute'), $this->proId);

        $newCategory = $this->newCategoryVersionOf($categoryId);
        self::assertNotNull($newCategory);
        self::assertSame('Beaute', $newCategory['name']);

        $this->assertReplaced($bookedId, $categoryId, (int) $newCategory['id']);
        $this->assertUpdatedInPlace($notBookedId, (int) $newCategory['id']);
    }

    #[Test]
    public function renaming_a_category_without_appointments_creates_no_new_version(): void
    {
        $categoryId = $this->insertCategory('Soins');
        $serviceId = $this->insertService($categoryId, 'Soin visage');

        $handler = new RenameServiceCategoryCommandHandler($this->services, $this->categories);
        $handler(new RenameServiceCategoryCommand($categoryId, 'Beaute'), $this->proId);

        self::assertNull($this->newCategoryVersionOf($categoryId));
        self::assertSame(
            'Beaute',
            $this->connection->fetchOne('SELECT name FROM service_category WHERE id = ?', [$categoryId])
        );
        $this->assertUpdatedInPlace($serviceId, $categoryId);
    }

    #[Test]
    public function deleting_a_category_and_keeping_services_creates_a_new_version_only_for_booked_services(): void
    {
        $categoryId = $this->insertCategory('Soins');
        $bookedId = $this->insertService($categoryId, 'Soin visage');
        $notBookedId = $this->insertService($categoryId, 'Soin corps');
        $this->insertAppointment($bookedId);

        $handler = new DeleteServiceCategoryCommandHandler($this->services, $this->categories);
        $handler(new DeleteServiceCategoryCommand($categoryId, true), $this->proId);

        $this->assertReplaced($bookedId, $categoryId, null);
        $this->assertUpdatedInPlace($notBookedId, null);
    }

    #[Test]
    public function deleting_a_category_with_keep_services_deletes_the_category_and_keeps_its_services(): void
    {
        $categoryId = $this->insertCategory('Soins');
        $otherCategoryId = $this->insertCategory('Massages');
        $bookedId = $this->insertService($categoryId, 'Soin visage');
        $notBookedId = $this->insertService($categoryId, 'Soin corps');
        $otherCategoryServiceId = $this->insertService($otherCategoryId, 'Massage');
        $appointmentId = $this->insertAppointment($bookedId);
        $appointmentBefore = $this->appointmentRow($appointmentId);

        $handler = new DeleteServiceCategoryCommandHandler($this->services, $this->categories);
        $handler(new DeleteServiceCategoryCommand($categoryId, true), $this->proId);

        $this->assertCategoryDeleted($categoryId);

        $this->assertReplaced($bookedId, $categoryId, null);
        $this->assertUpdatedInPlace($notBookedId, null);
        $this->assertUpdatedInPlace($otherCategoryServiceId, $otherCategoryId);

        $newVersion = $this->newServiceVersionOf($bookedId);
        self::assertNotNull($newVersion);
        self::assertSame('Soin visage', $newVersion['name']);
        self::assertSame(30, (int) $newVersion['duration_min']);
        self::assertSame(2500, (int) $newVersion['price_cents']);
        self::assertNotNull($newVersion['published_at']);

        self::assertSame($appointmentBefore, $this->appointmentRow($appointmentId));
    }

    #[Test]
    public function deleting_a_category_without_keep_services_deletes_the_category_and_its_services(): void
    {
        $categoryId = $this->insertCategory('Soins');
        $otherCategoryId = $this->insertCategory('Massages');
        $bookedId = $this->insertService($categoryId, 'Soin visage');
        $notBookedId = $this->insertService($categoryId, 'Soin corps');
        $otherCategoryServiceId = $this->insertService($otherCategoryId, 'Massage');
        $appointmentId = $this->insertAppointment($bookedId);
        $appointmentBefore = $this->appointmentRow($appointmentId);

        $handler = new DeleteServiceCategoryCommandHandler($this->services, $this->categories);
        $handler(new DeleteServiceCategoryCommand($categoryId, false), $this->proId);

        $this->assertCategoryDeleted($categoryId);

        foreach ([$bookedId, $notBookedId] as $serviceId) {
            $service = $this->serviceRow($serviceId);
            self::assertNotNull($service['deleted_at']);
            self::assertSame('deleted', $service['deletion_reason']);
            self::assertSame($categoryId, (int) $service['category_id']);
            self::assertNull($this->newServiceVersionOf($serviceId));
        }

        $this->assertUpdatedInPlace($otherCategoryServiceId, $otherCategoryId);

        self::assertSame($appointmentBefore, $this->appointmentRow($appointmentId));
    }

    #[Test]
    public function editing_the_content_of_a_booked_service_creates_a_new_version(): void
    {
        $categoryId = $this->insertCategory('Soins');
        $serviceId = $this->insertService($categoryId, 'Soin visage');
        $this->insertAppointment($serviceId);

        $this->updateService($serviceId, 'Soin du visage', $categoryId, true);

        $this->assertReplaced($serviceId, $categoryId, $categoryId);
        $newVersion = $this->newServiceVersionOf($serviceId);
        self::assertNotNull($newVersion);
        self::assertSame('Soin du visage', $newVersion['name']);
        self::assertNotNull($newVersion['published_at']);
    }

    #[Test]
    public function editing_only_the_publication_of_a_booked_service_creates_no_new_version(): void
    {
        $categoryId = $this->insertCategory('Soins');
        $serviceId = $this->insertService($categoryId, 'Soin visage');
        $this->insertAppointment($serviceId);

        $this->updateService($serviceId, 'Soin visage', $categoryId, false);

        $this->assertUpdatedInPlace($serviceId, $categoryId);
        self::assertNull(
            $this->connection->fetchOne('SELECT published_at FROM service WHERE id = ?', [$serviceId])
        );
    }

    #[Test]
    public function editing_the_content_of_a_service_without_appointments_creates_no_new_version(): void
    {
        $categoryId = $this->insertCategory('Soins');
        $serviceId = $this->insertService($categoryId, 'Soin visage');

        $this->updateService($serviceId, 'Soin du visage', $categoryId, true);

        $this->assertUpdatedInPlace($serviceId, $categoryId);
        self::assertSame(
            'Soin du visage',
            $this->connection->fetchOne('SELECT name FROM service WHERE id = ?', [$serviceId])
        );
    }

    #[Test]
    public function renaming_a_booked_category_sets_deleted_at_on_the_old_service_version_only(): void
    {
        $categoryId = $this->insertCategory('Soins');
        $serviceId = $this->insertService($categoryId, 'Soin visage');
        $appointmentId = $this->insertAppointment($serviceId);

        $handler = new RenameServiceCategoryCommandHandler($this->services, $this->categories);
        $handler(new RenameServiceCategoryCommand($categoryId, 'Beaute'), $this->proId);

        $oldCategory = $this->connection->fetchAssociative(
            'SELECT * FROM service_category WHERE id = ?',
            [$categoryId]
        );
        self::assertIsArray($oldCategory);
        self::assertNotNull($oldCategory['deleted_at']);
        self::assertSame('replaced', $oldCategory['deletion_reason']);

        $this->assertOldVersionRetiredAndNewVersionInUse($serviceId, $appointmentId);
        self::assertSame($oldCategory['deleted_at'], $this->serviceRow($serviceId)['deleted_at']);
    }

    #[Test]
    public function deleting_a_booked_category_and_keeping_services_sets_deleted_at_on_the_old_version_only(): void
    {
        $categoryId = $this->insertCategory('Soins');
        $serviceId = $this->insertService($categoryId, 'Soin visage');
        $appointmentId = $this->insertAppointment($serviceId);

        $handler = new DeleteServiceCategoryCommandHandler($this->services, $this->categories);
        $handler(new DeleteServiceCategoryCommand($categoryId, true), $this->proId);

        $oldCategory = $this->connection->fetchAssociative(
            'SELECT * FROM service_category WHERE id = ?',
            [$categoryId]
        );
        self::assertIsArray($oldCategory);
        self::assertNotNull($oldCategory['deleted_at']);
        self::assertSame('deleted', $oldCategory['deletion_reason']);

        $this->assertOldVersionRetiredAndNewVersionInUse($serviceId, $appointmentId);
        self::assertSame($oldCategory['deleted_at'], $this->serviceRow($serviceId)['deleted_at']);
    }

    #[Test]
    public function moving_a_booked_service_to_another_category_sets_deleted_at_on_the_old_version_only(): void
    {
        $categoryId = $this->insertCategory('Soins');
        $otherCategoryId = $this->insertCategory('Massages');
        $serviceId = $this->insertService($categoryId, 'Soin visage');
        $appointmentId = $this->insertAppointment($serviceId);

        $this->updateService($serviceId, 'Soin visage', $otherCategoryId, true);

        $this->assertOldVersionRetiredAndNewVersionInUse($serviceId, $appointmentId);
        $this->assertReplaced($serviceId, $categoryId, $otherCategoryId);
    }

    #[Test]
    public function removing_the_category_of_a_booked_service_sets_deleted_at_on_the_old_version_only(): void
    {
        $categoryId = $this->insertCategory('Soins');
        $serviceId = $this->insertService($categoryId, 'Soin visage');
        $appointmentId = $this->insertAppointment($serviceId);

        $this->updateService($serviceId, 'Soin visage', null, true);

        $this->assertOldVersionRetiredAndNewVersionInUse($serviceId, $appointmentId);
        $this->assertReplaced($serviceId, $categoryId, null);
    }

    #[Test]
    public function the_appointment_count_is_only_available_when_it_was_requested(): void
    {
        $categoryId = $this->insertCategory('Soins');
        $serviceId = $this->insertService($categoryId, 'Soin visage');
        $this->insertAppointment($serviceId);

        $withCount = $this->services->findNotDeleted($serviceId, $this->proId, withAppointmentCount: true);
        self::assertNotNull($withCount);
        self::assertTrue($withCount->hasAppointments());

        $withoutCount = $this->services->findNotDeleted($serviceId, $this->proId);
        self::assertNotNull($withoutCount);
        $this->expectException(\LogicException::class);
        $withoutCount->hasAppointments();
    }

    /**
     * The old row is retired and keeps its category; a new row references it.
     */
    private function assertReplaced(int $serviceId, int $oldCategoryId, ?int $newCategoryId): void
    {
        $old = $this->connection->fetchAssociative('SELECT * FROM service WHERE id = ?', [$serviceId]);
        self::assertIsArray($old);
        self::assertNotNull($old['deleted_at']);
        self::assertSame('replaced', $old['deletion_reason']);
        self::assertSame($oldCategoryId, (int) $old['category_id']);

        $newVersion = $this->newServiceVersionOf($serviceId);
        self::assertNotNull($newVersion);
        self::assertNull($newVersion['deleted_at']);
        self::assertSame($newCategoryId, $newVersion['category_id'] !== null ? (int) $newVersion['category_id'] : null);
    }

    /**
     * The old version has deleted_at and keeps its appointment; the new version it leads to has no deleted_at.
     */
    private function assertOldVersionRetiredAndNewVersionInUse(int $serviceId, int $appointmentId): void
    {
        $old = $this->serviceRow($serviceId);
        self::assertNotNull($old['deleted_at']);
        self::assertSame('replaced', $old['deletion_reason']);

        $newVersion = $this->newServiceVersionOf($serviceId);
        self::assertNotNull($newVersion);
        self::assertNull($newVersion['deleted_at']);
        self::assertNull($newVersion['deletion_reason']);

        self::assertSame(
            $serviceId,
            (int) $this->connection->fetchOne('SELECT service_id FROM appointment WHERE id = ?', [$appointmentId])
        );
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
     * @return array<string, mixed>
     */
    private function appointmentRow(int $appointmentId): array
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM appointment WHERE id = ?', [$appointmentId]);
        self::assertIsArray($row);

        return $row;
    }

    /**
     * The category is explicitly deleted, not replaced by a new version.
     */
    private function assertCategoryDeleted(int $categoryId): void
    {
        $category = $this->connection->fetchAssociative('SELECT * FROM service_category WHERE id = ?', [$categoryId]);
        self::assertIsArray($category);
        self::assertNotNull($category['deleted_at']);
        self::assertSame('deleted', $category['deletion_reason']);
        self::assertNull($this->newCategoryVersionOf($categoryId));
    }

    /**
     * The same row is still in use, in the expected category, and no new row references it.
     */
    private function assertUpdatedInPlace(int $serviceId, ?int $categoryId): void
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM service WHERE id = ?', [$serviceId]);
        self::assertIsArray($row);
        self::assertNull($row['deleted_at']);
        self::assertNull($row['deletion_reason']);
        self::assertSame($categoryId, $row['category_id'] !== null ? (int) $row['category_id'] : null);
        self::assertNull($this->newServiceVersionOf($serviceId));
    }

    private function updateService(int $serviceId, string $name, ?int $categoryId, bool $published): void
    {
        $handler = new UpdateServiceCommandHandler(
            $this->services,
            $this->categories,
            new ServiceImageFiles($this->createStub(ServiceImageStorageInterface::class), new NullLogger())
        );

        $handler(
            new UpdateServiceCommand($serviceId, $name, null, 30, 2500, $categoryId, $published),
            $this->proId,
            []
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function newServiceVersionOf(int $serviceId): ?array
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM service WHERE previous_version_id = ?', [$serviceId]);

        return $row === false ? null : $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function newCategoryVersionOf(int $categoryId): ?array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM service_category WHERE previous_version_id = ?',
            [$categoryId]
        );

        return $row === false ? null : $row;
    }

    private function insertCategory(string $name): int
    {
        return (int) $this->connection->fetchOne(
            'INSERT INTO service_category (pro_id, name) VALUES (?, ?) RETURNING id',
            [$this->proId, $name]
        );
    }

    /**
     * A published service without description, 30 minutes and 25 euros, like the edit commands of these tests.
     */
    private function insertService(int $categoryId, string $name): int
    {
        return (int) $this->connection->fetchOne(
            'INSERT INTO service (pro_id, category_id, name, duration_min, price_cents, published_at)
             VALUES (?, ?, ?, 30, 2500, NOW())
             RETURNING id',
            [$this->proId, $categoryId, $name]
        );
    }

    private function insertAppointment(int $serviceId): int
    {
        return (int) $this->connection->fetchOne(
            "INSERT INTO appointment (pro_id, service_id, start_dt, end_dt)
             VALUES (?, ?, '2099-06-01 09:00:00', '2099-06-01 09:30:00')
             RETURNING id",
            [$this->proId, $serviceId]
        );
    }
}
