<?php

declare(strict_types=1);

namespace Tests\Driver;

use PHPUnit\Framework\TestCase;
use Webman\Limiter\Driver\Redis as RedisDriver;

final class RedisExpireTriggerTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testExpireExtendEvalTriggeredOnlyWhenTargetIncreases(): void
    {
        if (!in_array((string)getenv('RATE_LIMITER_RUN_REDIS_TESTS'), ['1', 'true', 'y', 'yes'], true)) {
            $this->markTestSkipped('Redis driver tests are disabled. Re-run and answer "y" when prompted.');
        }
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('PHP redis extension is not installed; skipping Redis driver tests.');
        }

        try {
            \support\Redis::connection('default')->ttl('__rate_limiter_test_ping__');
        } catch (\Throwable) {
            $host = (string)config('redis.default.host', '127.0.0.1');
            $port = (int)config('redis.default.port', 6379);
            $db = (int)config('redis.default.database', 0);
            $this->markTestSkipped("Unable to connect to Redis at {$host}:{$port} db={$db}; skipping Redis driver tests.");
        }

        $baseNow = time();

        $driver = new class(null, 'default', $baseNow) extends RedisDriver {
            public int $extendCalls = 0;

            public function __construct($worker, string $connection, private int $baseNow)
            {
                parent::__construct($worker, $connection);
            }

            protected function now(): int
            {
                return $this->baseNow;
            }

            protected function getExpireTime(int $ttl, int $time): int
            {
                // Keep windowStart constant across different ttls:
                // windowStart = expireTime - ttl = baseNow
                return $this->baseNow + $ttl;
            }

            protected function ceilToTtl(int $timestamp, int $ttl): int
            {
                // Deterministic targets relative to "now" to avoid past expirations.
                // ttl=100 -> target=now+5001 (triggers)
                // ttl=80  -> target=now+4001 (decreasing, should NOT trigger)
                // ttl=120 -> target=now+6001 (increasing, should trigger)
                return match ($ttl) {
                    100 => $this->baseNow + 5000,
                    80 => $this->baseNow + 4000,
                    120 => $this->baseNow + 6000,
                    default => $this->baseNow + 5000,
                };
            }

            protected function extendExpire(object $connection, string $hashKey, int $expireAtTarget): int
            {
                $this->extendCalls++;
                return parent::extendExpire($connection, $hashKey, $expireAtTarget);
            }
        };

        // Clean the daily hashKey used by this deterministic setup.
        $dayStart = strtotime(date('Y-m-d', $baseNow));
        $hashKey = 'limiter-' . date('Y-m-d', $dayStart);
        \support\Redis::connection('default')->del($hashKey);

        // 1) First call triggers eval (cache empty).
        $driver->increase('k', 100);
        $this->assertSame(1, $driver->extendCalls);

        // 2) Same ttl should not trigger eval again.
        $driver->increase('k', 100);
        $this->assertSame(1, $driver->extendCalls);

        // 3) Decreasing ttl should not trigger eval.
        $driver->increase('k', 80);
        $this->assertSame(1, $driver->extendCalls);

        // 4) Repeated decreasing ttl should also not trigger eval.
        $driver->increase('k', 80);
        $this->assertSame(1, $driver->extendCalls);

        // 5) Larger ttl produces a larger target expireAt -> triggers eval again.
        $driver->increase('k', 120);
        $this->assertSame(2, $driver->extendCalls);
    }
}

