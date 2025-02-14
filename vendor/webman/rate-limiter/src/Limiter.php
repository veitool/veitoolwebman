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

namespace Webman\RateLimiter;

use Webman\RateLimiter\Driver\Apcu;
use Webman\RateLimiter\Driver\DriverInterface;
use Webman\RateLimiter\Driver\Memory;
use Webman\RateLimiter\Driver\Redis;
use Exception;
use RedisException;
use ReflectionException;
use ReflectionMethod;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use Webman\RateLimiter\Annotation\RateLimiter as RateLimiterAnnotation;
use Workerman\Worker;

/**
 * Class Limiter
 */
class Limiter implements MiddlewareInterface
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
     * @var bool
     */
    protected static bool $initialized = false;

    /**
     * @var string
     */
    protected static string $prefix = 'rate-limiter';

    /**
     * 中间件逻辑
     * @param Request $request
     * @param callable $handler
     * @return Response
     * @throws ReflectionException|Exception
     */
    public function process(Request $request, callable $handler) : Response
    {
        if (!$request->controller || !method_exists($request->controller, $request->action)) {
            return $handler($request);
        }

        $reflectionMethod = new ReflectionMethod($request->controller, $request->action);
        $attributes = $reflectionMethod->getAttributes(RateLimiterAnnotation::class);

        $prefix = static::$prefix;
        foreach ($attributes as $attribute) {
            $annotation = $attribute->newInstance();
            switch ($annotation->key) {
                case RateLimiterAnnotation::UID:
                    $uid = session('user.id', session()->getId());
                    $key = "$prefix-$request->controller-$request->action-$annotation->key-$uid";
                    break;
                case RateLimiterAnnotation::SID:
                    $key = "$prefix-$request->controller-$request->action-$annotation->key-" . session()->getId();
                    break;
                case RateLimiterAnnotation::IP:
                    $ip = $request->getRealIp();
                    if (in_array($ip, static::$ipWhiteList)) {
                        continue 2;
                    }
                    $key = "$prefix-$request->controller-$request->action-$annotation->key-$ip";
                    break;
                default:
                    if (is_array($annotation->key)) {
                        $key = $prefix . '-' . ($annotation->key)();
                    } else {
                        $key = "$prefix-$annotation->key";
                    }
            }
            if (static::$driver->increase($key, $annotation->ttl) > $annotation->limit) {
                $exceptionClass = $annotation->exception;
                throw new $exceptionClass($annotation->message);
            }
        }

        return $handler($request);
    }

    /**
     * @param Worker|null $worker
     * @return void
     * @throws RedisException
     */
    public static function init(?Worker $worker): void
    {
        static::$initialized = true;
        static::$ipWhiteList = config('plugin.webman.rate-limiter.app.ip_whitelist', []);
        static::$redisConnection = config('plugin.webman.rate-limiter.app.stores.redis.connection', 'default');
        $driver = config('plugin.webman.rate-limiter.app.driver');
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
     * Check rate limit
     * @param string $key
     * @param int $limit
     * @param int $ttl
     * @param string $message
     * @return void
     * @throws RateLimitException
     */
    public static function check(string $key, int $limit, int $ttl, string $message = 'Too Many Requests'): void
    {
        $key = static::$prefix . '-' . $key;
        if (static::$driver->increase($key, $ttl) > $limit) {
            throw new RateLimitException($message);
        }
    }
}