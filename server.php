<?php

declare(strict_types=1);

/**
 * CnA Framework - Built-in Development Web Server Router
 * 
 * Emulates web server rewrite rules for php -S.
 * Static files are returned directly; all other routes dispatch to index.php.
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'
);

$publicPath = __DIR__ . '/public';
if (!is_dir($publicPath) && is_dir(__DIR__ . '/skeleton/public')) {
    $publicPath = __DIR__ . '/skeleton/public';
}

$file = $publicPath . $uri;

// If the requested URI is a static asset that exists, return false to serve it natively
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

require_once $publicPath . '/index.php';

