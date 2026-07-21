<?php

// https://getrector.com/documentation

if (!defined('ITIMER_REAL')) {
    define('ITIMER_REAL', 0);
}

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withBootstrapFiles([
        // __DIR__ . '/app/Helpers/helpers.php',
        // __DIR__ . '/app/Helpers/inject_php.php',
        // __DIR__ . '/public/index.php',
        // __DIR__ . '/app/Core/init.php',
        // __DIR__ . '/servers/bootstrap.php',
    ])
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/cron',
        __DIR__ . '/static',
        __DIR__ . '/public',
        __DIR__ . '/views',
    ])
    ->withSkip([
        __DIR__ . '/app/Core/Security/Middleware/JwtToken.php',
        __DIR__ . '/app/Core/Database/BpDatabase.php',
        __DIR__ . '/app/Core/Database/BpQuery.php',
        __DIR__ . '/app/Core/Message/Transport/BpTransport.php',
        __DIR__ . '/app/Core/Database/Mappers/*',
        __DIR__ . '/app/Controllers/backend-htmx/*',
        __DIR__ . '/views/htmx/old/*',
        __DIR__ . '/tests/Fixtures',
    ])
    ->withPhpVersion(PhpVersion::PHP_85)
    ->withPhpSets(php84: true);
