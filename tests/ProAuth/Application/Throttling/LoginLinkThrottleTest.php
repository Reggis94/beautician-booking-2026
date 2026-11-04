<?php

namespace App\Tests\ProAuth\Application\Throttling;

use App\ProAuth\Application\Throttling\LoginLinkCooldown;
use App\ProAuth\Application\Throttling\LoginLinkRateLimit;
use App\ProAuth\Application\Throttling\LoginLinkThrottle;
use App\ProAuth\Infrastructure\Dao\LoginTokenDao;
use App\Tests\DatabaseTestCase;
use Doctrine\DBAL\ParameterType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Login link throttling requirements. These scenarios are the authoritative documentation of the business contract,
 * so the numbers are written out on purpose.
 *
 * Every scenario runs in a single database transaction, so NOW() is the same instant for the seeded past requests
 * and for the check under test: the 60-second and one-hour boundaries are exact.
 */
final class LoginLinkThrottleTest extends DatabaseTestCase
{
    private int $proId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection->beginTransaction();
        $this->proId = $this->insertPro('jane.doe@example.com', 'jane-doe');
    }

    #[Test]
    public function a_first_login_link_request_is_allowed(): void
    {
        self::assertTrue($this->throttle()->isAllowed($this->proId));
    }

    #[Test]
    public function another_request_after_59_seconds_is_refused(): void
    {
        $this->givenLoginLinkRequestedSecondsAgo($this->proId, 59);

        self::assertFalse($this->throttle()->isAllowed($this->proId));
    }

    #[Test]
    public function after_exactly_60_seconds_another_request_is_allowed(): void
    {
        $this->givenLoginLinkRequestedSecondsAgo($this->proId, 60);

        self::assertTrue($this->throttle()->isAllowed($this->proId));
    }

    #[Test]
    public function with_23_requests_in_the_last_hour_one_more_is_allowed(): void
    {
        $this->givenLoginLinksRequestedInTheLastHour($this->proId, 23);

        self::assertTrue($this->throttle()->isAllowed($this->proId));
    }

    #[Test]
    #[DataProvider('requestsReachingTheRateLimit')]
    public function with_24_or_more_requests_in_the_last_hour_another_is_refused(int $requestsInTheLastHour): void
    {
        $this->givenLoginLinksRequestedInTheLastHour($this->proId, $requestsInTheLastHour);

        self::assertFalse($this->throttle()->isAllowed($this->proId));
    }

    /**
     * @return array<string, array{int}>
     */
    public static function requestsReachingTheRateLimit(): array
    {
        return [
            '24 requests' => [24],
            '25 requests' => [25],
        ];
    }

    #[Test]
    public function requests_exactly_one_hour_old_no_longer_count(): void
    {
        $this->givenLoginLinkRequestedSecondsAgo($this->proId, 3600);
        $this->givenLoginLinksRequestedInTheLastHour($this->proId, 23);

        self::assertTrue($this->throttle()->isAllowed($this->proId));
    }

    #[Test]
    public function requests_from_another_beautician_do_not_affect_these_limits(): void
    {
        $otherProId = $this->insertPro('john.smith@example.com', 'john-smith');
        $this->givenLoginLinkRequestedSecondsAgo($otherProId, 0);
        $this->givenLoginLinksRequestedInTheLastHour($otherProId, 24);

        self::assertTrue($this->throttle()->isAllowed($this->proId));
    }

    /**
     * A 60-second cooldown and at most 24 requests per hour.
     */
    private function throttle(): LoginLinkThrottle
    {
        $loginTokenDao = new LoginTokenDao($this->connection);

        return new LoginLinkThrottle(
            new LoginLinkCooldown($loginTokenDao, 60),
            new LoginLinkRateLimit($loginTokenDao, 24, 3600)
        );
    }

    private function insertPro(string $email, string $linkSlug): int
    {
        return (int) $this->connection->fetchOne(
            'INSERT INTO pro (email, link_slug) VALUES (:email, :link_slug) RETURNING id',
            ['email' => $email, 'link_slug' => $linkSlug]
        );
    }

    private function givenLoginLinkRequestedSecondsAgo(int $proId, int $secondsAgo): void
    {
        $this->connection->executeStatement(
            "INSERT INTO pro_login_token (pro_id, token_hash, created_at)
             VALUES (:pro_id, encode(sha256(gen_random_uuid()::text::bytea), 'hex'),
                     NOW() - make_interval(secs => :seconds_ago))",
            ['pro_id' => $proId, 'seconds_ago' => $secondsAgo],
            ['pro_id' => ParameterType::INTEGER, 'seconds_ago' => ParameterType::INTEGER]
        );
    }

    /**
     * Requests one minute apart, the most recent one 60 seconds ago, so the cooldown is over.
     */
    private function givenLoginLinksRequestedInTheLastHour(int $proId, int $count): void
    {
        for ($minutesAgo = 1; $minutesAgo <= $count; $minutesAgo++) {
            $this->givenLoginLinkRequestedSecondsAgo($proId, $minutesAgo * 60);
        }
    }
}
