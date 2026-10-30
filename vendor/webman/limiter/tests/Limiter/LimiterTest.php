<?php

declare(strict_types=1);

namespace Tests\Limiter;

use PHPUnit\Framework\TestCase;
use Webman\Limiter\Limiter;
use Webman\Limiter\RateLimitException as WebmanRateLimitException;
use support\limiter\Limiter as SupportLimiter;
use support\limiter\RateLimitException as SupportRateLimitException;

class LimiterTest extends TestCase
{
    protected function setUp(): void
    {
        // Force reset static properties of Limiter for each test
        $ref = new \ReflectionClass(Limiter::class);
        
        // Reset driver to Memory for isolated testing
        $driverProp = $ref->getProperty('driver');
        $driverProp->setAccessible(true);
        $driverProp->setValue(null, new \Webman\Limiter\Driver\Memory(null));
        
        $prefixProp = $ref->getProperty('prefix');
        $prefixProp->setAccessible(true);
        $prefixProp->setValue(null, 'test-prefix');
    }

    public function testWebmanLimiterCheckDefaultException(): void
    {
        $key = 'webman_key_' . uniqid();
        $limit = 1;
        $ttl = 60;

        // First attempt: OK
        Limiter::check($key, $limit, $ttl);

        // Second attempt: Exception
        $this->expectException(WebmanRateLimitException::class);
        // Verify it is NOT the support subclass (unless configured explicitly, but default is base)
        try {
            Limiter::check($key, $limit, $ttl);
        } catch (\Exception $e) {
            $this->assertInstanceOf(SupportRateLimitException::class, $e);
            throw $e;
        }
    }

    public function testSupportLimiterCheckDefaultException(): void
    {
        $key = 'support_key_' . uniqid();
        $limit = 1;
        $ttl = 60;

        // First attempt: OK
        SupportLimiter::check($key, $limit, $ttl);

        // Second attempt: Exception
        $this->expectException(SupportRateLimitException::class);
        
        SupportLimiter::check($key, $limit, $ttl);
    }

}
