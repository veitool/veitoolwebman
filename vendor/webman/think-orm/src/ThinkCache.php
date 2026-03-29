<?php

declare(strict_types=1);

namespace Webman\ThinkOrm;

use DateInterval;
use DateTimeInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use support\think\Cache;

/**
 * Cache adapter for ThinkORM.
 */
class ThinkCache implements CacheInterface
{
    /**
     * Clear the entire cache pool.
     * @access public
     * @return bool
     * @throws ReflectionException
     */
    public function clear(): bool
    {
        return Cache::clear();
    }

    /**
     * Get a cache entry.
     * @access public
     * @param string $key Cache key
     * @param mixed $default Default value if missing
     * @return mixed
     * @throws InvalidArgumentException|ReflectionException
     */
    public function get($key, mixed $default = null): mixed
    {
        return Cache::get($key,  $default);
    }

    /**
     * Set a cache entry.
     * @access public
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @param int|DateTimeInterface|DateInterval $ttl TTL; 0 means no expiry
     * @return bool
     * @throws InvalidArgumentException|ReflectionException
     */
    public function set($key, $value, $ttl = null): bool
    {
        return Cache::set($key,  $value, $ttl);
    }

    /**
     * Delete a cache entry.
     * @access public
     * @param string $key Cache key
     * @return bool
     * @throws InvalidArgumentException|ReflectionException
     */
    public function delete($key): bool
    {
        return Cache::delete($key);
    }

    /**
     * Get multiple cache entries.
     * @access public
     * @param iterable $keys Cache keys
     * @param mixed $default Default value for missing keys
     * @return iterable
     * @throws ReflectionException
     */
    public function getMultiple($keys, $default = null): iterable
    {
        return Cache::getMultiple($keys, $default);
    }

    /**
     * Set multiple cache entries.
     * @access public
     * @param iterable $values Key-value pairs
     * @param null|int|DateInterval $ttl TTL; 0 means no expiry
     * @return bool
     * @throws ReflectionException
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return Cache::setMultiple($values, $ttl);
    }

    /**
     * Delete multiple cache entries.
     * @access public
     * @param iterable $keys Cache keys
     * @return bool
     * @throws ReflectionException
     */
    public function deleteMultiple($keys): bool
    {
        return Cache::deleteMultiple($keys);
    }

    /**
     * Whether a cache key exists.
     * @access public
     * @param string $key Cache key
     * @return bool
     * @throws InvalidArgumentException|ReflectionException
     */
    public function has($key): bool
    {
        return Cache::has($key);
    }

    /**
     * Increment a numeric cache value (used by ThinkORM lazy-write field accumulation).
     *
     * @param string $name Cache key
     * @param float|int $step Step amount
     * @return mixed
     * @throws ReflectionException
     */
    public function inc(string $name, float|int $step = 1): mixed
    {
        return Cache::inc($name, $step);
    }

    /**
     * Decrement a numeric cache value (used by ThinkORM lazy-write field accumulation).
     *
     * @param string $name Cache key
     * @param float|int $step Step amount
     * @return mixed
     * @throws ReflectionException
     */
    public function dec(string $name, float|int $step = 1): mixed
    {
        return Cache::dec($name, $step);
    }
}
