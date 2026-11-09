<?php

namespace App\Tests\Services\Application;

use App\Services\Application\ImageFile\ServiceImageFiles;
use App\Services\Application\Port\ServiceImageStorageInterface;
use App\Services\Application\Service\Command\CreateServiceCommand;
use App\Services\Application\Service\Command\DeleteServiceCommand;
use App\Services\Application\Service\Command\UpdateServiceCommand;
use App\Services\Application\Service\CommandHandler\CreateServiceCommandHandler;
use App\Services\Application\Service\CommandHandler\DeleteServiceCommandHandler;
use App\Services\Application\Service\CommandHandler\UpdateServiceCommandHandler;
use App\Services\Application\ServiceCategory\Command\CreateServiceCategoryCommand;
use App\Services\Application\ServiceCategory\Command\DeleteServiceCategoryCommand;
use App\Services\Application\ServiceCategory\Command\RenameServiceCategoryCommand;
use App\Services\Application\ServiceCategory\CommandHandler\CreateServiceCategoryCommandHandler;
use App\Services\Application\ServiceCategory\CommandHandler\DeleteServiceCategoryCommandHandler;
use App\Services\Application\ServiceCategory\CommandHandler\RenameServiceCategoryCommandHandler;
use App\Services\Infrastructure\Repository\ServiceCategoryRepository;
use App\Services\Infrastructure\Repository\ServiceRepository;
use App\Tests\DatabaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;

final class CatalogLifecycleTest extends DatabaseTestCase
{
    private ServiceRepository $services;
    private ServiceCategoryRepository $categories;
    private ServiceImageFiles $imageFiles;
    private int $proId;
    private int $categoryId;
    private int $serviceId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->services = new ServiceRepository($this->connection);
        $this->categories = new ServiceCategoryRepository($this->connection);
        $this->imageFiles = new ServiceImageFiles(
            $this->createStub(ServiceImageStorageInterface::class),
            new NullLogger()
        );
        $this->proId = (int) $this->connection->fetchOne(
            "INSERT INTO pro (email, link_slug) VALUES ('jane.doe@example.com', 'jane-doe') RETURNING id"
        );
        (new CreateServiceCategoryCommandHandler($this->services, $this->categories))(
            new CreateServiceCategoryCommand('Soins'),
            $this->proId
        );
        $this->categoryId = (int) $this->connection->fetchOne('SELECT id FROM service_category');
        (new CreateServiceCommandHandler($this->services, $this->categories, $this->imageFiles))(
            new CreateServiceCommand('Soin visage', null, 30, 2500, $this->categoryId, true),
            $this->proId,
            []
        );
        $this->serviceId = (int) $this->connection->fetchOne('SELECT id FROM service');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function catalogRecords(): iterable
    {
        yield 'service' => ['service'];
        yield 'category' => ['service_category'];
    }

    #[Test]
    #[DataProvider('catalogRecords')]
    public function explicit_deletion_sets_deleted_without_creating_a_version(string $table): void
    {
        $id = $table === 'service' ? $this->serviceId : $this->categoryId;
        $created = $this->row($table, $id);
        self::assertNull($created['deleted_at']);
        self::assertNull($created['deletion_reason']);
        self::assertNull($created['previous_version_id']);

        $this->delete($table, $id);

        $deleted = $this->row($table, $id);
        self::assertNotNull($deleted['deleted_at']);
        self::assertSame('deleted', $deleted['deletion_reason']);
        self::assertNull($deleted['previous_version_id']);
        self::assertSame(1, (int) $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $table)));
    }

    #[Test]
    #[DataProvider('catalogRecords')]
    public function editing_booked_content_then_deleting_the_replacement_preserves_history(string $table): void
    {
        $appointmentId = (int) $this->connection->fetchOne(
            "INSERT INTO appointment (pro_id, service_id, start_dt, end_dt)
             VALUES (?, ?, '2099-06-01 09:00:00', '2099-06-01 09:30:00') RETURNING id",
            [$this->proId, $this->serviceId]
        );
        $id = $table === 'service' ? $this->serviceId : $this->categoryId;
        $before = $this->row($table, $id);

        if ($table === 'service') {
            (new UpdateServiceCommandHandler($this->services, $this->categories, $this->imageFiles))(
                new UpdateServiceCommand($id, 'Nouveau soin', null, 30, 2500, $this->categoryId, true),
                $this->proId,
                []
            );
        } else {
            (new RenameServiceCategoryCommandHandler($this->services, $this->categories))(
                new RenameServiceCategoryCommand($id, 'Nouveau soin'),
                $this->proId
            );
        }

        $retired = $this->row($table, $id);
        self::assertNotNull($retired['deleted_at']);
        self::assertSame('replaced', $retired['deletion_reason']);
        self::assertSame($before['name'], $retired['name']);
        $replacement = $this->connection->fetchAssociative(
            sprintf('SELECT * FROM %s WHERE previous_version_id = ?', $table),
            [$id]
        );
        self::assertIsArray($replacement);
        self::assertNotSame($id, (int) $replacement['id']);
        self::assertSame($id, (int) $replacement['previous_version_id']);
        self::assertSame('Nouveau soin', $replacement['name']);
        self::assertNull($replacement['deleted_at']);
        self::assertNull($replacement['deletion_reason']);

        $this->delete($table, (int) $replacement['id']);

        $deleted = $this->row($table, (int) $replacement['id']);
        self::assertNotNull($deleted['deleted_at']);
        self::assertSame('deleted', $deleted['deletion_reason']);
        self::assertSame($id, (int) $deleted['previous_version_id']);
        self::assertSame($retired, $this->row($table, $id));
        self::assertSame(2, (int) $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $table)));
        self::assertSame(
            $this->serviceId,
            (int) $this->connection->fetchOne('SELECT service_id FROM appointment WHERE id = ?', [$appointmentId])
        );
        self::assertSame($this->categoryId, (int) $this->row('service', $this->serviceId)['category_id']);
    }

    private function delete(string $table, int $id): void
    {
        if ($table === 'service') {
            (new DeleteServiceCommandHandler($this->services))(new DeleteServiceCommand($id), $this->proId);
        } else {
            (new DeleteServiceCategoryCommandHandler($this->services, $this->categories))(
                new DeleteServiceCategoryCommand($id, false),
                $this->proId
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function row(string $table, int $id): array
    {
        $row = $this->connection->fetchAssociative(sprintf('SELECT * FROM %s WHERE id = ?', $table), [$id]);
        self::assertIsArray($row);

        return $row;
    }
}
