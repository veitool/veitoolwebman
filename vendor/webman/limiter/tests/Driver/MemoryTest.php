<?php

declare(strict_types=1);

namespace Tests\Driver;

use PHPUnit\Framework\TestCase;
use Webman\Limiter\Driver\Memory;

final class MemoryTest extends TestCase
{
    public function testIncreaseIncrements(): void
    {
        $driver = new Memory(null);
        $key = 'k1';

        $this->assertSame(1, $driver->increase($key, 60));
        $this->assertSame(2, $driver->increase($key, 60));
        $this->assertSame(3, $driver->increase($key, 60, 1));
        $this->assertSame(8, $driver->increase($key, 60, 5));
    }

    public function testDifferentTtlIsolated(): void
    {
        $driver = new Memory(null);
        $key = 'k2';

        $this->assertSame(1, $driver->increase($key, 60));
        $this->assertSame(1, $driver->increase($key, 120));
        $this->assertSame(2, $driver->increase($key, 60));
        $this->assertSame(2, $driver->increase($key, 120));
    }
}

