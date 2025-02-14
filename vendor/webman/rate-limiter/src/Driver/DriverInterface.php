<?php

namespace Webman\RateLimiter\Driver;

interface DriverInterface
{
    public function increase(string $key, int $ttl = 24*60*60, $step = 1): int;
}