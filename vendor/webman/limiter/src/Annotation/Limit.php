<?php

namespace Webman\Limiter\Annotation;

use Attribute;
use support\limiter\RateLimitException;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
class Limit
{
    /**
     * IP
     */
    const IP = 'ip';

    /**
     * UID
     */
    const UID = 'uid';

    /**
     * SID
     */
    const SID = 'sid';

    /**
     * Limit constructor.
     * @param int $limit
     * @param int $ttl
     * @param mixed $key
     * @param string $message
     * @param string $exception
     */
    public function __construct(public int $limit, public int $ttl = 1, public mixed $key = 'ip', public string $message = 'Too Many Requests', public string $exception = RateLimitException::class) {}
}