<?php

namespace App\Tests\ProAuth\Infrastructure\Dao;

use App\ProAuth\Application\Token\OpaqueToken;
use App\ProAuth\Infrastructure\Dao\LoginTokenDao;
use App\Tests\DatabaseTestCase;
use Doctrine\DBAL\ParameterType;
use PHPUnit\Framework\Attributes\Test;

/**
 * Login link requirements decided by the database: which beauticians may request a login link, and which login
 * tokens confirm a login.
 */
final class LoginTokenDaoTest extends DatabaseTestCase
{
    private LoginTokenDao $loginTokenDao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginTokenDao = new LoginTokenDao($this->connection);
    }

    #[Test]
    public function a_login_link_can_be_requested_for_the_email_of_a_beautician(): void
    {
        $proId = $this->insertPro('jane.doe@example.com', 'jane-doe');

        self::assertSame($proId, $this->loginTokenDao->findActiveProIdByEmail('jane.doe@example.com'));
    }

    #[Test]
    public function a_login_link_cannot_be_requested_for_an_unknown_email(): void
    {
        $this->insertPro('jane.doe@example.com', 'jane-doe');

        self::assertNull($this->loginTokenDao->findActiveProIdByEmail('unknown@example.com'));
    }

    #[Test]
    public function a_login_link_cannot_be_requested_for_a_deleted_beautician(): void
    {
        $proId = $this->insertPro('jane.doe@example.com', 'jane-doe');
        $this->deletePro($proId);

        self::assertNull($this->loginTokenDao->findActiveProIdByEmail('jane.doe@example.com'));
    }

    #[Test]
    public function a_login_token_confirms_the_login_of_its_beautician(): void
    {
        $proId = $this->insertPro('jane.doe@example.com', 'jane-doe');
        $loginToken = $this->givenLoginToken($proId);

        self::assertSame($proId, $this->loginTokenDao->consume(OpaqueToken::hash($loginToken)));
    }

    #[Test]
    public function a_login_token_confirms_a_login_only_once(): void
    {
        $proId = $this->insertPro('jane.doe@example.com', 'jane-doe');
        $loginToken = $this->givenLoginToken($proId);
        $this->loginTokenDao->consume(OpaqueToken::hash($loginToken));

        self::assertNull($this->loginTokenDao->consume(OpaqueToken::hash($loginToken)));
    }

    #[Test]
    public function an_unknown_login_token_confirms_no_login(): void
    {
        $proId = $this->insertPro('jane.doe@example.com', 'jane-doe');
        $this->givenLoginToken($proId);

        self::assertNull($this->loginTokenDao->consume('unknown-token-hash'));
    }

    #[Test]
    public function a_login_token_whose_email_was_not_delivered_confirms_no_login(): void
    {
        $proId = $this->insertPro('jane.doe@example.com', 'jane-doe');
        $loginToken = $this->givenLoginToken($proId, isDeliveryFailed: true);

        self::assertNull($this->loginTokenDao->consume(OpaqueToken::hash($loginToken)));
    }

    #[Test]
    public function a_login_token_of_a_deleted_beautician_confirms_no_login(): void
    {
        $proId = $this->insertPro('jane.doe@example.com', 'jane-doe');
        $loginToken = $this->givenLoginToken($proId);
        $this->deletePro($proId);

        self::assertNull($this->loginTokenDao->consume(OpaqueToken::hash($loginToken)));
    }

    private function insertPro(string $email, string $linkSlug): int
    {
        return (int) $this->connection->fetchOne(
            'INSERT INTO pro (email, link_slug) VALUES (:email, :link_slug) RETURNING id',
            ['email' => $email, 'link_slug' => $linkSlug]
        );
    }

    private function deletePro(int $proId): void
    {
        $this->connection->executeStatement(
            'UPDATE pro SET deleted_at = NOW() WHERE id = :pro_id',
            ['pro_id' => $proId],
            ['pro_id' => ParameterType::INTEGER]
        );
    }

    /**
     * Stores the hash of a new login token, as a login link request does, and returns the token sent by email.
     */
    private function givenLoginToken(int $proId, bool $isDeliveryFailed = false): string
    {
        $loginToken = OpaqueToken::generate();
        $this->connection->executeStatement(
            'INSERT INTO pro_login_token (pro_id, token_hash, delivery_failed_at)
             VALUES (:pro_id, :token_hash, CASE WHEN :is_delivery_failed THEN NOW() END)',
            [
                'pro_id' => $proId,
                'token_hash' => OpaqueToken::hash($loginToken),
                'is_delivery_failed' => $isDeliveryFailed,
            ],
            [
                'pro_id' => ParameterType::INTEGER,
                'token_hash' => ParameterType::STRING,
                'is_delivery_failed' => ParameterType::BOOLEAN,
            ]
        );

        return $loginToken;
    }
}
