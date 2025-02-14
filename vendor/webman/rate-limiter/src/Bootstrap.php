<?php

namespace Webman\RateLimiter;

use Workerman\Worker;
use RedisException;

/**
 * Class Session
 * @package support
 */
class Bootstrap implements \Webman\Bootstrap
{

    /**
     * @param Worker|null $worker
     * @return void
     * @throws RedisException
     */
    public static function start(?Worker $worker)
    {
        Limiter::init($worker);
    }
}
