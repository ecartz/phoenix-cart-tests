<?php

declare(strict_types=1);

/**
 * Router for `php -S` so Phoenix Request::* getenv() checks see HTTP_* values.
 */

if (PHP_SAPI !== 'cli-server') {
    return false;
}

static $synced = false;
if (!$synced) {
    foreach ($_SERVER as $key => $value) {
        if (!is_string($value)) {
            continue;
        }

        if (str_starts_with($key, 'HTTP_') || $key === 'REMOTE_ADDR') {
            putenv($key . '=' . $value);
        }
    }

    $synced = true;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$file = $_SERVER['DOCUMENT_ROOT'] . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

if (is_file($file . '.php')) {
    require $file . '.php';

    return true;
}

if (is_file($file)) {
    return false;
}

require $_SERVER['DOCUMENT_ROOT'] . '/index.php';

return true;
