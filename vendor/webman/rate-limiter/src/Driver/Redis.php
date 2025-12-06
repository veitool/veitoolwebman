<?php

namespace Webman\RateLimiter\Driver;

use RedisException;
use support\Redis as RedisClient;
use Workerman\Worker;

class Redis implements DriverInterface
{
    /**
     * @param Worker|null $worker
     * @param string $connection
     * @throws RedisException
     */
    public function __construct(?Worker $worker, protected string $connection)
    {
        
    }
    
    /**
     * @throws RedisException
     */
    public function increase(string $key, int $ttl = 24 * 60 * 60, $step = 1): int
    {
        static $hashKeyExpireAtMap = [];
        $connection = RedisClient::connection($this->connection);
        
        // 计算当前请求所属 TTL 窗口的过期时间点（固定窗口）
        $expireTime = $this->getExpireTime($ttl);
        
        // 以天为最小分段：TTL 向上取整到天，得到分段长度（单位：秒）
        $segmentDays = (int)ceil($ttl / (24 * 60 * 60));
        if ($segmentDays < 1) {
            $segmentDays = 1;
        }
        $segmentSeconds = $segmentDays * 24 * 60 * 60;
        
        // 找到窗口过期时间所在“天”的起始时刻（本地时间），并据此锚定到 N 天段的起始时刻
        $expireDayStart = strtotime(date('Y-m-d', $expireTime));
        $segmentStart = (int) (floor($expireDayStart / $segmentSeconds) * $segmentSeconds);
        $segmentEnd = $segmentStart + $segmentSeconds;
        
        // 使用分段起始日作为 hashKey 的日期部分
        $hashKey = 'rate-limiter-' . date('Y-m-d', $segmentStart);
        
        // field 按窗口过期时间与 TTL 唯一标识该 TTL 窗口
        $field = $key . '-' . $expireTime . '-' . $ttl;

        $count = $connection->hIncrBy($hashKey, $field, $step) ?: 0;

        // 设置当前 hashKey 的过期时间为分段结束时刻，仅延长不过期
        $expireAt = $segmentEnd;
        $currentRecordedExpireAt = $hashKeyExpireAtMap[$hashKey] ?? null;
        if ($currentRecordedExpireAt === null || $expireAt > $currentRecordedExpireAt) {
            // 仅在需要更新 Redis 过期时间时才清理内存 map，避免频繁遍历
            if (!empty($hashKeyExpireAtMap)) {
                $now = time();
                foreach ($hashKeyExpireAtMap as $k => $expAt) {
                    if ($expAt <= $now) {
                        unset($hashKeyExpireAtMap[$k]);
                    }
                }
            }

            $connection->expireAt($hashKey, $expireAt);
            $hashKeyExpireAtMap[$hashKey] = $expireAt;
        }

        return $count;
    }

    /**
     * @param $ttl
     * @return int
     */
    protected function getExpireTime($ttl): int
    {
        return ceil(time() / $ttl) * $ttl;
    }
}
