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
        static $lastExpiredHashKey = null;
        $connection = RedisClient::connection($this->connection);
        $hashKey = 'rate-limiter-' . date('Y-m-d');
        $field = $key . '-' . $this->getExpireTime($ttl) . '-' . $ttl;

        $count = $connection->hIncrBy($hashKey, $field, $step) ?: 0;

        if ($lastExpiredHashKey !== $hashKey) {
            $expireAt = strtotime(date('Y-m-d') . ' 23:59:59') + 300;
            $connection->expireAt($hashKey, $expireAt);
            $lastExpiredHashKey = $hashKey;
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
