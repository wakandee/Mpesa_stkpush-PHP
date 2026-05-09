<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});

require_once __DIR__ . '/Support/Helpers.php';

App\Support\Env::load(__DIR__ . '/../.env');

$timezone = App\Support\Env::get('APP_TIMEZONE', 'Africa/Nairobi');
date_default_timezone_set($timezone);
