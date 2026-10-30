<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Webman\Limiter;

use Webman\Limiter\Driver\Apcu;
use Webman\Limiter\Driver\DriverInterface;
use Webman\Limiter\Driver\Memory;
use Webman\Limiter\Driver\Redis;
use RedisException;
use Workerman\Worker;
use support\limiter\RateLimitException;

/**
 * Class Limiter
 */
class Limiter
{
    /**
     * @var array
     */
    protected static array $ipWhiteList = [];

    /**
     * @var DriverInterface
     */
    protected static DriverInterface $driver;

    /**
     * @var string
     */
    protected static string $redisConnection = 'default';

    /**
     * @var string
     */
    protected static string $prefix = 'limiter';
    
    /**
     * @param Worker|null $worker
     * @return void
     * @throws RedisException
     */
    public static function init(?Worker $worker): void
    {
        static::$ipWhiteList = config('plugin.webman.limiter.app.ip_whitelist', []);
        static::$redisConnection = config('plugin.webman.limiter.app.stores.redis.connection', 'default');
        $driver = config('plugin.webman.limiter.app.driver');
        if ($driver === 'auto') {
            if (function_exists('apcu_enabled') && apcu_enabled()) {
                $driver = 'apcu';
            } else {
                $driver = 'memory';
            }
        }
        static::$driver = match ($driver) {
            'apcu' => new Apcu($worker),
            'redis' => new Redis($worker, static::$redisConnection),
            default => new Memory($worker),
        };
    }

    /**
     * Attempt to consume one hit for the given key.
     *
     * Returns true if the current request is within the limit, false if the
     * limit has been exceeded.
     *
     * @param string $key
     * @param int $limit
     * @param int $ttl
     * @return bool
     * @throws RedisException
     */
    public static function attempt(string $key, int $limit, int $ttl): bool
    {
        $storageKey = static::$prefix . '-' . $key;
        return static::$driver->increase($storageKey, $ttl) <= $limit;
    }

    /**
     * Check rate limit and throw an exception when exceeded.
     *
     * @param string $key
     * @param int $limit
     * @param int $ttl
     * @param string $message
     * @return void
     * @throws RateLimitException
     */
    public static function check(string $key, int $limit, int $ttl, string $message = 'Too Many Requests'): void
    {
        if (!static::attempt($key, $limit, $ttl)) {
            $exceptionClass = config('plugin.webman.limiter.app.exception', RateLimitException::class);
            throw new $exceptionClass($message, 429);
        }
    }

    /**
     * Determine whether the given IP is in the whitelist.
     *
     * @param string $ip
     * @return bool
     */
    public static function isIpWhiteListed(string $ip): bool
    {
        foreach (static::$ipWhiteList as $allowIp) {
            if (static::ipInRange($ip, $allowIp)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check whether the IP address is within the given CIDR or wildcard range.
     *
     * @param string $ip
     * @param string $range
     * @return bool
     */
    protected static function ipInRange(string $ip, string $range): bool
    {
        if (str_contains($range, '/')) {
            [$subnet, $bits] = explode('/', $range);
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $mask = -1 << (32 - (int)$bits);

            return ($ipLong & $mask) === ($subnetLong & $mask);
        }

        $rangeRegex = str_replace('.', '\.', $range);
        $rangeRegex = str_replace('*', '[0-9]+', $rangeRegex);

        return preg_match('/^' . $rangeRegex . '$/', $ip) === 1;
    }

}