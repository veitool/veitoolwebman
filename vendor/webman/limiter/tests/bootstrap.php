<?php

declare(strict_types=1);

/**
 * Test bootstrap.
 * - Provide an interactive prompt (y/N) to decide whether to run Redis driver tests.
 * - Default is NO (press Enter).
 */

date_default_timezone_set('Asia/Shanghai');

require_once __DIR__ . '/../vendor/autoload.php';

// Load test configs into webman Config so that webman/redis can read `config('redis')`.
if (class_exists(\Webman\Config::class)) {
    \Webman\Config::clear();
    \Webman\Config::load(__DIR__ . '/config');
}

// Default: do not run Redis tests.
$runRedis = false;

if (getenv('RATE_LIMITER_RUN_REDIS_TESTS') !== false) {
    $runRedis = in_array(strtolower((string)getenv('RATE_LIMITER_RUN_REDIS_TESTS')), ['1', 'true', 'y', 'yes'], true);
} else {
    $isTty = false;
    if (defined('STDIN') && function_exists('stream_isatty')) {
        $isTty = @stream_isatty(STDIN);
    }

    if ($isTty) {
        fwrite(STDOUT, "Run Redis driver tests? [y/N]: ");
        $line = fgets(STDIN);
        $answer = strtolower(trim((string)$line));
        $runRedis = in_array($answer, ['y', 'yes'], true);
    }
}

putenv('RATE_LIMITER_RUN_REDIS_TESTS=' . ($runRedis ? '1' : '0'));

// Persist the choice for tests running in separate processes.
// PHPUnit's `@runInSeparateProcess` does not reliably see `putenv()` changes across processes in some setups.
$flagFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rate_limiter_run_redis_tests.flag';
@file_put_contents($flagFile, $runRedis ? '1' : '0');

