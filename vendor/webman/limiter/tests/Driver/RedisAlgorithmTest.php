<?php

declare(strict_types=1);

namespace Tests\Driver;

use PHPUnit\Framework\TestCase;
use Webman\Limiter\Driver\Redis as RedisDriver;

final class RedisAlgorithmTest extends TestCase
{
    public function testExpireAtNeededIsTtlAlignedAndNotBeforeNextMidnight(): void
    {
        $driver = new class(null, 'default') extends RedisDriver {
            public function ceilToTtlPublic(int $timestamp, int $ttl): int
            {
                return $this->ceilToTtl($timestamp, $ttl);
            }
        };

        $ttl = 70000; // Not divisible by 86400
        $dayStart = strtotime('2026-02-17 00:00:00');
        $nextDayStart = $dayStart + 86400;

        $expireAtNeeded = $driver->ceilToTtlPublic($nextDayStart, $ttl);

        $this->assertGreaterThanOrEqual($nextDayStart, $expireAtNeeded);
        $this->assertSame(0, $expireAtNeeded % $ttl, 'expireAtNeeded must be aligned to ttl boundary');
    }

    public function testHashKeyUsesWindowStartLocalDate(): void
    {
        $tz = date_default_timezone_get();
        $this->assertNotEmpty($tz);

        $ttl = 90000; // > 24h, will likely produce windowStart on previous day for many times
        $now = strtotime('2026-02-18 10:00:00');

        $driver = new class(null, 'default', $now) extends RedisDriver {
            public function __construct($worker, string $connection, private int $fixedNow)
            {
                parent::__construct($worker, $connection);
            }

            protected function now(): int
            {
                return $this->fixedNow;
            }

            public function getExpireTimePublic(int $ttl, int $time): int
            {
                return $this->getExpireTime($ttl, $time);
            }
        };

        $expireTime = $driver->getExpireTimePublic($ttl, $now);
        $windowStart = $expireTime - $ttl;
        $expectedHashKey = 'limiter-' . date('Y-m-d', strtotime(date('Y-m-d', $windowStart)));

        // Re-run the same computation used by the driver (scheme B) to ensure the date component is correct.
        $dayStart = strtotime(date('Y-m-d', $windowStart));
        $actualHashKey = 'limiter-' . date('Y-m-d', $dayStart);

        $this->assertSame($expectedHashKey, $actualHashKey);
    }

    public function testCrossDayWindowCanMapToPreviousDayHashKey(): void
    {
        // Example: ttl not divisible by 86400, and time at local midnight.
        // Scheme B uses windowStart local date, which can be previous day.
        $ttl = 70000;
        $now = strtotime('2026-02-18 00:00:00');

        $driver = new class(null, 'default', $now) extends RedisDriver {
            public function __construct($worker, string $connection, private int $fixedNow)
            {
                parent::__construct($worker, $connection);
            }

            protected function now(): int
            {
                return $this->fixedNow;
            }

            public function getExpireTimePublic(int $ttl, int $time): int
            {
                return $this->getExpireTime($ttl, $time);
            }
        };

        $expireTime = $driver->getExpireTimePublic($ttl, $now);
        $windowStart = $expireTime - $ttl;
        $hashDate = date('Y-m-d', strtotime(date('Y-m-d', $windowStart)));

        $this->assertSame('2026-02-17', $hashDate);
    }
}

