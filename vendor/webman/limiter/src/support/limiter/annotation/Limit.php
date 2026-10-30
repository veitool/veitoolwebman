<?php

namespace support\limiter\annotation;

use Attribute;
use support\limiter\RateLimitException;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
class Limit extends \Webman\Limiter\Annotation\Limit
{
}
