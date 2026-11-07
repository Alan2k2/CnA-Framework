<?php

declare(strict_types=1);

$rootPath = dirname(__DIR__);

if (file_exists($rootPath . '/vendor/autoload.php')) {
    require_once $rootPath . '/vendor/autoload.php';
} else {
    spl_autoload_register(function ($class) use ($rootPath) {
        $prefixMap = [
            'CnA\\Contracts\\' => $rootPath . '/packages/contracts/src/',
            'CnA\\Core\\' => $rootPath . '/packages/core/src/',
            'CnA\\Container\\' => $rootPath . '/packages/container/src/',
            'CnA\\Config\\' => $rootPath . '/packages/config/src/',
            'CnA\\Console\\' => $rootPath . '/packages/console/src/',
            'CnA\\Http\\' => $rootPath . '/packages/http/src/',
            'CnA\\Routing\\' => $rootPath . '/packages/routing/src/',
            'CnA\\Middleware\\' => $rootPath . '/packages/middleware/src/',
            'CnA\\Validation\\' => $rootPath . '/packages/validation/src/',
            'CnA\\Database\\' => $rootPath . '/packages/database/src/',
            'CnA\\QueryBuilder\\' => $rootPath . '/packages/query-builder/src/',
            'CnA\\Orm\\' => $rootPath . '/packages/orm/src/',
            'CnA\\Migrations\\' => $rootPath . '/packages/migrations/src/',
            'CnA\\Auth\\' => $rootPath . '/packages/auth/src/',
            'CnA\\Authorization\\' => $rootPath . '/packages/authorization/src/',
            'CnA\\Security\\' => $rootPath . '/packages/security/src/',
            'CnA\\Cache\\' => $rootPath . '/packages/cache/src/',
            'CnA\\Queue\\' => $rootPath . '/packages/queue/src/',
            'CnA\\Events\\' => $rootPath . '/packages/events/src/',
            'CnA\\Scheduler\\' => $rootPath . '/packages/scheduler/src/',
            'CnA\\Mail\\' => $rootPath . '/packages/mail/src/',
            'CnA\\Notifications\\' => $rootPath . '/packages/notifications/src/',
            'CnA\\Storage\\' => $rootPath . '/packages/storage/src/',
            'CnA\\Ai\\' => $rootPath . '/packages/ai/src/',
            'CnA\\Logging\\' => $rootPath . '/packages/logging/src/',
            'CnA\\Monitoring\\' => $rootPath . '/packages/monitoring/src/',
            'CnA\\Testing\\' => $rootPath . '/packages/testing/src/',
            'App\\' => $rootPath . '/skeleton/app/',
        ];

        foreach ($prefixMap as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) === 0) {
                $relative = substr($class, $len);
                $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
                if (file_exists($file)) {
                    require_once $file;
                    return;
                }
            }
        }
    });

    if (file_exists($rootPath . '/packages/core/src/helpers.php')) {
        require_once $rootPath . '/packages/core/src/helpers.php';
    }
}
