<?php

namespace App\Tests\Services\Infrastructure;

use App\Tests\DatabaseTestCase;
use Doctrine\DBAL\Exception\DriverException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The database itself guarantees that deleted_at and deletion_reason are set together, whatever code writes the rows.
 */
final class DeletionReasonConstraintTest extends DatabaseTestCase
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
     * @return iterable<string, array{string, string}>
     */
    public static function refusedChanges(): iterable
    {
        foreach (['service', 'service_category'] as $table) {
            yield $table . ': deleted_at without a reason' => [$table, 'deleted_at = NOW()'];
            yield $table . ': a reason without deleted_at' => [$table, "deletion_reason = 'deleted'"];
            yield $table . ': an unknown reason' => [$table, "deleted_at = NOW(), deletion_reason = 'archived'"];
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function acceptedReasons(): iterable
    {
        foreach (['service', 'service_category'] as $table) {
            yield $table . ': deleted' => [$table, 'deleted'];
            yield $table . ': replaced' => [$table, 'replaced'];
        }
    }

    #[Test]
    #[DataProvider('refusedChanges')]
    public function an_inconsistent_deletion_is_refused(string $table, string $assignments): void
    {
        $id = $this->insertRow($table);

        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('ck_' . $table . '_deletion_reason');

        $this->connection->executeStatement(sprintf('UPDATE %s SET %s WHERE id = ?', $table, $assignments), [$id]);
    }

    #[Test]
    #[DataProvider('acceptedReasons')]
    public function a_deletion_with_a_known_reason_is_accepted(string $table, string $reason): void
    {
        $id = $this->insertRow($table);

        $this->connection->executeStatement(
            sprintf('UPDATE %s SET deleted_at = NOW(), deletion_reason = ? WHERE id = ?', $table),
            [$reason, $id]
        );

        self::assertSame(
            $reason,
            $this->connection->fetchOne(sprintf('SELECT deletion_reason FROM %s WHERE id = ?', $table), [$id])
        );
    }

    private function insertRow(string $table): int
    {
        return (int) $this->connection->fetchOne(
            sprintf('INSERT INTO %s (pro_id, name) VALUES (?, ?) RETURNING id', $table),
            [$this->proId, 'Row ' . uniqid()]
        );
    }
}
