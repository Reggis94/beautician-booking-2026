<?php

namespace App\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Runs SQL against a dedicated test database, never against the application database.
 *
 * The test database is the `DATABASE_URL` database with the `_test` suffix, as Doctrine names it in the `test`
 * environment. It is recreated from `data/update.sql` once per test run, so its schema always matches the branch,
 * and every table is emptied after each test. Tests are skipped when PostgreSQL is not reachable.
 */
abstract class DatabaseTestCase extends TestCase
{
    private const SCHEMA_FILE = __DIR__ . '/../data/update.sql';
    private const TEST_DATABASE_SUFFIX = '_test';

    private static bool $isTestDatabaseCreated = false;

    protected Connection $connection;

    /**
     * @var list<Connection>
     */
    private array $otherConnections = [];

    protected function setUp(): void
    {
        try {
            if (!self::$isTestDatabaseCreated) {
                self::createTestDatabase();
                self::$isTestDatabaseCreated = true;
            }

            $this->connection = self::connectToTestDatabase();
        } catch (\Throwable $exception) {
            self::markTestSkipped('The test database is not available: ' . $exception->getMessage());
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->otherConnections as $connection) {
            $connection->close();
        }

        if (!isset($this->connection)) {
            return;
        }

        while ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }

        $tables = $this->connection->fetchFirstColumn(
            "SELECT quote_ident(tablename) FROM pg_tables WHERE schemaname = 'public'"
        );
        if ($tables !== []) {
            $this->connection->executeStatement(
                sprintf('TRUNCATE %s RESTART IDENTITY CASCADE', implode(', ', $tables))
            );
        }

        $this->connection->close();
    }

    /**
     * A separate database session on the test database, used to observe locks and committed data.
     */
    protected function openOtherConnection(): Connection
    {
        $connection = self::connectToTestDatabase();
        $this->otherConnections[] = $connection;

        return $connection;
    }

    private static function createTestDatabase(): void
    {
        $applicationConnection = DriverManager::getConnection(self::applicationDatabaseParams());
        $testDatabase = $applicationConnection->quoteIdentifier(self::testDatabaseParams()['dbname']);

        try {
            $applicationConnection->executeStatement(sprintf('DROP DATABASE IF EXISTS %s WITH (FORCE)', $testDatabase));
            $applicationConnection->executeStatement(sprintf('CREATE DATABASE %s', $testDatabase));
        } finally {
            $applicationConnection->close();
        }

        $connection = self::connectToTestDatabase();
        try {
            $connection->executeStatement((string) file_get_contents(self::SCHEMA_FILE));
        } finally {
            $connection->close();
        }
    }

    private static function connectToTestDatabase(): Connection
    {
        return DriverManager::getConnection(self::testDatabaseParams());
    }

    /**
     * @return array<string, mixed>
     */
    private static function testDatabaseParams(): array
    {
        $params = self::applicationDatabaseParams();
        $params['dbname'] .= self::TEST_DATABASE_SUFFIX;

        // Guards the DROP DATABASE above against ever targeting a non-test database.
        if (!str_ends_with($params['dbname'], self::TEST_DATABASE_SUFFIX)) {
            throw new \LogicException('The test database name must end with ' . self::TEST_DATABASE_SUFFIX . '.');
        }

        return $params;
    }

    /**
     * @return array<string, mixed>
     */
    private static function applicationDatabaseParams(): array
    {
        $databaseUrl = $_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? null;

        if ($databaseUrl === null) {
            $envFile = dirname(self::SCHEMA_FILE, 2) . '/.env';
            if (is_file($envFile)) {
                $_SERVER['APP_ENV'] ??= 'test';
                (new Dotenv())->bootEnv($envFile);
            }
            $databaseUrl = $_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? null;
        }

        if ($databaseUrl === null) {
            throw new \RuntimeException('DATABASE_URL is not set.');
        }

        $params = DriverManager::getConnection(['url' => $databaseUrl])->getParams();
        unset($params['url']);

        if (!isset($params['dbname']) || !is_string($params['dbname'])) {
            throw new \RuntimeException('DATABASE_URL has no database name.');
        }

        return $params;
    }
}
