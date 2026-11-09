<?php

namespace App\Tests\Services\Infrastructure;

use App\Tests\DatabaseTestCase;
use Doctrine\DBAL\Exception\DriverException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The database itself guarantees that an old version always has deleted_at, whatever code writes the rows.
 */
final class OldVersionDeletedAtTest extends DatabaseTestCase
{
    private int $proId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proId = (int) $this->connection->fetchOne(
            "INSERT INTO pro (email, link_slug) VALUES ('jane.doe@example.com', 'jane-doe') RETURNING id"
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function versionedTables(): iterable
    {
        yield 'service' => ['service'];
        yield 'service_category' => ['service_category'];
    }

    #[Test]
    #[DataProvider('versionedTables')]
    public function a_new_version_cannot_reference_a_version_without_deleted_at(string $table): void
    {
        $oldVersionId = $this->insertVersion($table, null);

        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('must have deleted_at before a new version references it');

        $this->insertVersion($table, $oldVersionId);
    }

    #[Test]
    #[DataProvider('versionedTables')]
    public function an_existing_row_cannot_be_linked_to_a_version_without_deleted_at(string $table): void
    {
        $oldVersionId = $this->insertVersion($table, null);
        $otherId = $this->insertVersion($table, null);

        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('must have deleted_at before a new version references it');

        $this->connection->executeStatement(
            sprintf('UPDATE %s SET previous_version_id = ? WHERE id = ?', $table),
            [$oldVersionId, $otherId]
        );
    }

    #[Test]
    #[DataProvider('versionedTables')]
    public function an_old_version_cannot_lose_its_deleted_at(string $table): void
    {
        $oldVersionId = $this->insertRetiredVersion($table);
        $this->insertVersion($table, $oldVersionId);

        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('must keep its deleted_at');

        $this->connection->executeStatement(
            sprintf('UPDATE %s SET deleted_at = NULL, deletion_reason = NULL WHERE id = ?', $table),
            [$oldVersionId]
        );
    }

    #[Test]
    #[DataProvider('versionedTables')]
    public function a_new_version_can_reference_a_retired_version(string $table): void
    {
        $oldVersionId = $this->insertRetiredVersion($table);

        $newVersionId = $this->insertVersion($table, $oldVersionId);

        self::assertSame(
            $oldVersionId,
            (int) $this->connection->fetchOne(
                sprintf('SELECT previous_version_id FROM %s WHERE id = ?', $table),
                [$newVersionId]
            )
        );
    }

    #[Test]
    #[DataProvider('versionedTables')]
    public function a_deleted_row_without_a_newer_version_can_be_restored(string $table): void
    {
        $id = $this->insertRetiredVersion($table);

        $this->connection->executeStatement(
            sprintf('UPDATE %s SET deleted_at = NULL, deletion_reason = NULL WHERE id = ?', $table),
            [$id]
        );

        self::assertNull(
            $this->connection->fetchOne(sprintf('SELECT deleted_at FROM %s WHERE id = ?', $table), [$id])
        );
    }

    private function insertVersion(string $table, ?int $previousVersionId): int
    {
        return (int) $this->connection->fetchOne(
            sprintf('INSERT INTO %s (pro_id, name, previous_version_id) VALUES (?, ?, ?) RETURNING id', $table),
            [$this->proId, 'Version ' . uniqid(), $previousVersionId]
        );
    }

    private function insertRetiredVersion(string $table): int
    {
        return (int) $this->connection->fetchOne(
            sprintf(
                "INSERT INTO %s (pro_id, name, deleted_at, deletion_reason)
                 VALUES (?, ?, NOW(), 'replaced')
                 RETURNING id",
                $table
            ),
            [$this->proId, 'Version ' . uniqid()]
        );
    }
}
