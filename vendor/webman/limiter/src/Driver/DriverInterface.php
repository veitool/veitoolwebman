<?php

namespace Webman\Limiter\Driver;

interface DriverInterface
{
    public function increase(string $key, int $ttl = 1, int $step = 1): int;
}