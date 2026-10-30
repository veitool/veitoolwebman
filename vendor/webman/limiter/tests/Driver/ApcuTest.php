<?php

declare(strict_types=1);

namespace Tests\Driver;

use PHPUnit\Framework\TestCase;
use Webman\Limiter\Driver\Apcu;

final class ApcuTest extends TestCase
{
    public function testIncreaseIncrementsWhenApcuAvailable(): void
    {
        if (!extension_loaded('apcu') || !function_exists('apcu_enabled') || !apcu_enabled()) {
            $this->markTestSkipped('APCu extension is not installed or not enabled; skipping APCu driver tests.');
        }

        $driver = new Apcu(null);
        $key = 'k-apcu';

        $this->assertSame(1, $driver->increase($key, 3600));
        $this->assertSame(2, $driver->increase($key, 3600));
        $this->assertSame(7, $driver->increase($key, 3600, 5));
    }

    public function testDifferentTtlIsolatedWhenApcuAvailable(): void
    {
        if (!extension_loaded('apcu') || !function_exists('apcu_enabled') || !apcu_enabled()) {
            $this->markTestSkipped('APCu extension is not installed or not enabled; skipping APCu driver tests.');
        }

        $driver = new Apcu(null);
        $key = 'k-apcu-ttl';

        $this->assertSame(1, $driver->increase($key, 60));
        $this->assertSame(1, $driver->increase($key, 120));
        $this->assertSame(2, $driver->increase($key, 60));
        $this->assertSame(2, $driver->increase($key, 120));
    }
}

