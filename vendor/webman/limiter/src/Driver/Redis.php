<?php

namespace Webman\Limiter\Driver;

use RedisException;
use support\Redis as RedisClient;
use Workerman\Worker;

class Redis implements DriverInterface
{
    /**
     * Lua script: extend key expiry, never shorten.
     * Returns 1 if expiry was set/extended, 0 if no change was needed.
     *
     * KEYS[1] = hash key
     * ARGV[1] = target expireAt timestamp (PHP already adds +1s conservatively).
     */
    protected const LUA_EXTEND_EXPIRE = <<<'LUA'
local target_expire = tonumber(ARGV[1])

local ttl = redis.call('TTL', KEYS[1])
-- ttl: -2 (no key) | -1 (no expire) | >=0 (remaining seconds, rounded down)
if ttl < 0 then
    return redis.call('EXPIREAT', KEYS[1], target_expire)
end

local now = tonumber(redis.call('TIME')[1])
-- TTL is rounded down, so the real expireAt is in (now+ttl, now+ttl+1] in general,
-- but target_expire is integer seconds. So `target_expire > now+ttl` implies
-- `target_expire >= now+ttl+1`, which is guaranteed to be >= the real expireAt.
-- This keeps the logic simple while never shortening expiry.
local current_lower = now + ttl
if target_expire > current_lower then
    return redis.call('EXPIREAT', KEYS[1], target_expire)
end
return 0
LUA;

    /**
     * @param Worker|null $worker
     * @param string $connection
     */
    public function __construct(?Worker $worker, protected string $connection)
    {
    }

    /**
     * @throws RedisException
     */
    public function increase(string $key, int $ttl = 1, int $step = 1): int
    {
        static $hashKeyExpireAtMap = [];
        static $lastCleanupAt = 0;
        $connection = RedisClient::connection($this->connection);

        $ttl = max(1, $ttl);
        $step = (int)$step;
        $now = $this->now();

        // Fixed window: window end timestamp aligned to ttl on epoch.
        $expireTime = $this->getExpireTime($ttl, $now);

        // Scheme B: bucket by local date of the window start (one hash per day).
        $windowStart = $expireTime - $ttl;
        $dayStart = strtotime(date('Y-m-d', $windowStart)); // Local 00:00 (ignore DST).
        $nextDayStart = $dayStart + 86400;

        $hashKey = 'limiter-' . date('Y-m-d', $dayStart);
        $field = $key . '-' . $expireTime . '-' . $ttl;

        // The hashKey expiry must cover the end boundary of the day's last window.
        $expireAtNeeded = $this->ceilToTtl($nextDayStart, $ttl);
        // Conservative +1s to avoid under-estimation due to TTL second rounding.
        $expireAtTarget = $expireAtNeeded + 1;

        // Hot path: only HINCRBY (1 Redis call).
        $count = $connection->hIncrBy($hashKey, $field, $step) ?: 0;

        // Only attempt to extend expiry when local cache indicates it may be needed.
        if ($expireAtTarget > ($hashKeyExpireAtMap[$hashKey] ?? 0)) {
            $res = $this->extendExpire($connection, $hashKey, $expireAtTarget);
            // If Lua returns 1, we set/extended expiry.
            // If it returns 0, some process already has an expiry >= target.
            // In both cases we can advance the local cache to avoid redundant Lua calls.
            if ($res === 0 || $res === 1) {
                $hashKeyExpireAtMap[$hashKey] = $expireAtTarget;
            }

            // Low-frequency cleanup to avoid iterating on every request.
            if ($now - $lastCleanupAt >= 60) {
                foreach ($hashKeyExpireAtMap as $k => $v) {
                    if ($v <= $now) {
                        unset($hashKeyExpireAtMap[$k]);
                    }
                }
                $lastCleanupAt = $now;
            }
        }

        return (int)$count;
    }

    /**
     * Calculate current window end timestamp using integer math.
     *
     * @param int $ttl
     * @return int
     */
    protected function getExpireTime(int $ttl, int $time): int
    {
        $remainder = $time % $ttl;
        return $remainder === 0 ? $time : $time + ($ttl - $remainder);
    }

    /**
     * Ceil a timestamp to the next ttl-aligned boundary (epoch-aligned).
     *
     * @param int $timestamp
     * @param int $ttl
     * @return int
     */
    protected function ceilToTtl(int $timestamp, int $ttl): int
    {
        $ttl = max(1, $ttl);
        $remainder = $timestamp % $ttl;
        return $remainder === 0 ? $timestamp : $timestamp + ($ttl - $remainder);
    }

    /**
     * Get current time.
     * Kept as a method to allow unit tests to inject a fake time.
     * @return int
     */
    protected function now(): int
    {
        return time();
    }

    /**
     * Extend expireAt on the hash key.
     * Kept as a method to allow unit tests to count/observe the call.
     * @param object $connection
     * @param string $hashKey
     * @param int $expireAtTarget
     * @return int
     */
    protected function extendExpire(object $connection, string $hashKey, int $expireAtTarget): int
    {
        return (int)$connection->eval(static::LUA_EXTEND_EXPIRE, 1, $hashKey, $expireAtTarget);
    }
}
