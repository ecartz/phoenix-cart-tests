<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$candidates = [];

if (getenv('PHOENIX_CART_ROOT')) {
    $candidates[] = rtrim((string) getenv('PHOENIX_CART_ROOT'), DIRECTORY_SEPARATOR);
}

$candidates[] = realpath(__DIR__ . '/../../PhoenixCart') ?: __DIR__ . '/../../PhoenixCart';
$candidates[] = realpath(__DIR__ . '/../PhoenixCart') ?: __DIR__ . '/../PhoenixCart';
$candidates[] = realpath(__DIR__ . '/..') ?: __DIR__ . '/..';

$catalog_root = null;

foreach ($candidates as $candidate) {
    $autoloader = $candidate . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoloader.php';
    if (is_file($autoloader)) {
        $catalog_root = $candidate;
        break;
    }
}

if ($catalog_root === null) {
    throw new RuntimeException(
        'Phoenix Cart catalog root not found. Set PHOENIX_CART_ROOT or clone CE-PhoenixCart/PhoenixCart beside this repo.'
    );
}

if (!defined('DIR_FS_CATALOG')) {
    define('DIR_FS_CATALOG', $catalog_root . DIRECTORY_SEPARATOR);
}

if (!defined('DIR_FS_ADMIN')) {
    define('DIR_FS_ADMIN', DIR_FS_CATALOG . 'admin' . DIRECTORY_SEPARATOR);
}

if (!defined('PHOENIX_TEST_RUNNING')) {
    define('PHOENIX_TEST_RUNNING', true);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $_SESSION = [];
    session_start();
}

require_once DIR_FS_CATALOG . 'includes/system/class_index.php';
require_once DIR_FS_CATALOG . 'includes/system/autoloader.php';

$class_index = catalog_autoloader::register();
$GLOBALS['class_index'] = $class_index;

// Phoenix indexes versioned files by filename (e.g. Text) but autoloads via normalize_class_name (text).
$indexed_files = $class_index->get_files();
foreach ($indexed_files as $class => $path) {
    $normalized = class_index::normalize_class_name($class);
    if ($class !== $normalized && !array_key_exists($normalized, $indexed_files)) {
        $class_index->set($class, $path);
        $indexed_files[$normalized] = $path;
    }
}
