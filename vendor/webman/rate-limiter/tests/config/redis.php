<?php

// Minimal redis config for webman/redis in unit tests.
// Default: 127.0.0.1:6379, use DB 15 to avoid touching app data.

$host = getenv('RATE_LIMITER_TEST_REDIS_HOST') ?: '127.0.0.1';
$port = (int)(getenv('RATE_LIMITER_TEST_REDIS_PORT') ?: 6379);
$db = (int)(getenv('RATE_LIMITER_TEST_REDIS_DB') ?: 15);

return [
    'default' => [
        'password' => '',
        'host' => $host,
        'port' => $port,
        'database' => $db,
        'pool' => [
            'max_connections' => 5,
            'min_connections' => 1,
            'wait_timeout' => 3,
            'idle_timeout' => 60,
            'heartbeat_interval' => 50,
        ],
    ],
];

