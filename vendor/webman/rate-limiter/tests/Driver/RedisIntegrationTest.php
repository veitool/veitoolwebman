<?php

declare(strict_types=1);

namespace Tests\Driver;

use PHPUnit\Framework\TestCase;
use Webman\RateLimiter\Driver\Redis as RedisDriver;

final class RedisIntegrationTest extends TestCase
{
    private function shouldRunRedis(): bool
    {
        $env = getenv('RATE_LIMITER_RUN_REDIS_TESTS');
        if ($env !== false && in_array(strtolower((string)$env), ['1', 'true', 'y', 'yes'], true)) {
            return true;
        }

        // Fallback for separate-process tests: read persisted choice from temp file.
        $flagFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rate_limiter_run_redis_tests.flag';
        if (is_file($flagFile)) {
            $v = trim((string)@file_get_contents($flagFile));
            return $v === '1';
        }
        return false;
    }

    private function requireRedisOrSkip(): void
    {
        if (!$this->shouldRunRedis()) {
            $this->markTestSkipped('Redis driver tests are disabled. Re-run and answer "y" when prompted.');
        }
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('PHP redis extension is not installed; skipping Redis driver tests.');
        }

        $host = (string)config('redis.default.host', '127.0.0.1');
        $port = (int)config('redis.default.port', 6379);
        $db = (int)config('redis.default.database', 0);
        try {
            // Ensure Redis is reachable.
            \support\Redis::connection('default')->ttl('__rate_limiter_test_ping__');
        } catch (\Throwable $e) {
            $this->markTestSkipped("Unable to connect to Redis at {$host}:{$port} db={$db}; skipping Redis driver tests.");
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIncreaseIncrementsAndSetsExpireOnlyWhenNeeded(): void
    {
        $this->requireRedisOrSkip();

        // Freeze time for deterministic window/field calculations.
        $fixedNow = strtotime('2026-02-18 10:00:00');

        $driver = new class(null, 'default', $fixedNow) extends RedisDriver {
            public function __construct($worker, string $connection, private int $fixedNow)
            {
                parent::__construct($worker, $connection);
            }

            protected function now(): int
            {
                return $this->fixedNow;
            }

            public function meta(string $key, int $ttl): array
            {
                $expireTime = $this->getExpireTime($ttl, $this->now());
                $windowStart = $expireTime - $ttl;
                $dayStart = strtotime(date('Y-m-d', $windowStart));
                $nextDayStart = $dayStart + 86400;
                $hashKey = 'rate-limiter-' . date('Y-m-d', $dayStart);
                $field = $key . '-' . $expireTime . '-' . $ttl;
                $expireAtNeeded = $this->ceilToTtl($nextDayStart, $ttl);
                return [$hashKey, $field, $expireAtNeeded];
            }
        };

        [$hashKey, $field] = $driver->meta('k-redis', 60);

        // Cleanup possible leftovers from previous runs.
        $conn = \support\Redis::connection('default');
        $conn->del($hashKey);

        $this->assertSame(1, $driver->increase('k-redis', 60));
        $this->assertSame(2, $driver->increase('k-redis', 60));

        $stored = $conn->hGet($hashKey, $field);
        $this->assertSame('2', (string)$stored);

        $ttlSeconds = $conn->ttl($hashKey);
        $this->assertGreaterThan(0, $ttlSeconds, 'Hash key must have an expire time in Redis');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testExpireExtendNeverShortens(): void
    {
        $this->requireRedisOrSkip();

        $conn = \support\Redis::connection('default');
        $driver = new class(null, 'default') extends RedisDriver {
            public static function script(): string
            {
                return self::LUA_EXTEND_EXPIRE;
            }
        };

        $hashKey = 'rate-limiter-test-expire-extend-' . getmypid();
        $conn->del($hashKey);
        $conn->hIncrBy($hashKey, 'f', 1);

        $serverNow = time();
        if (method_exists($conn, 'time')) {
            $t = $conn->time();
            $serverNow = (int)$t[0];
        }

        $expireAt1 = $serverNow + 3600;
        $expireAt2 = $serverNow + 60;

        $script = $driver::script();
        $res1 = (int)$conn->eval($script, 1, $hashKey, $expireAt1);
        $this->assertSame(1, $res1);
        $ttl1 = (int)$conn->ttl($hashKey);
        $this->assertGreaterThan(3000, $ttl1, 'Hash key TTL must be close to 1 hour after first extend.');

        $res2 = (int)$conn->eval($script, 1, $hashKey, $expireAt2);
        // 0: no need to set (already longer)
        $this->assertSame(0, $res2, 'Second extend must not shorten.');

        $ttl2 = (int)$conn->ttl($hashKey);
        $this->assertGreaterThan(3000, $ttl2, 'Hash key TTL must not be shortened by the second extend.');
    }
}

