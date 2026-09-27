<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Define catalog DB connection constants from environment (wave 3).
 */
final class mysql_bootstrap
{
    public static function is_enabled(): bool
    {
        $flag = getenv('PHOENIX_MYSQL_ENABLED');

        return $flag !== false && $flag !== '' && $flag !== '0';
    }

    public static function define_connection_constants(): void
    {
        self::define_if_missing('DB_SERVER', self::env('PHOENIX_DB_HOST', '127.0.0.1'));
        self::define_if_missing('DB_SERVER_USERNAME', self::env('PHOENIX_DB_USER', 'phoenix'));
        self::define_if_missing('DB_SERVER_PASSWORD', self::env('PHOENIX_DB_PASSWORD', 'phoenix'));
        self::define_if_missing('DB_DATABASE', self::env('PHOENIX_DB_NAME', 'phoenix_test'));

        self::define_if_missing('HTTP_SERVER', 'https://shop.example.com');
        self::define_if_missing('DIR_WS_CATALOG', '/');
    }

    public static function define_catalog_language_constants(): void
    {
        if (!defined('TEXT_UNKNOWN_TAX_RATE')) {
            define('TEXT_UNKNOWN_TAX_RATE', 'Unknown tax rate');
        }
    }

    private static function env(string $name, string $default): string
    {
        $value = getenv($name);

        return ($value !== false && $value !== '') ? $value : $default;
    }

    private static function define_if_missing(string $name, string $value): void
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }
}
