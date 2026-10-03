<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Wave 7 — Apache HTTPS shop (SSL_SESSION_ID in PHP env).
 */
final class https_bootstrap
{
    public static function is_enabled(): bool {
        $flag = getenv('PHOENIX_HTTPS_ENABLED');

        return $flag !== false && $flag !== '' && $flag !== '0';
    }

    public static function base_url(): string {
        return rtrim(self::env('PHOENIX_HTTPS_BASE_URL', 'https://127.0.0.1:8443'), '/');
    }

    public static function apply_ssl_session_check_sql(): void {
        mysql_bootstrap::define_connection_constants();

        $path = dirname(__DIR__, 2) . '/fixtures/http/enable_ssl_session_check.sql';
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new \RuntimeException('Cannot read ' . $path);
        }

        $mysqli = new \mysqli(
            (string) DB_SERVER,
            (string) DB_SERVER_USERNAME,
            (string) DB_SERVER_PASSWORD,
            (string) DB_DATABASE
        );

        if ($mysqli->connect_errno) {
            throw new \RuntimeException('MySQL connect failed: ' . $mysqli->connect_error);
        }

        $mysqli->set_charset('utf8mb4');

        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            if ($statement === '' || str_starts_with($statement, '#')) {
                continue;
            }
            if (!$mysqli->query($statement)) {
                $mysqli->close();
                throw new \RuntimeException('SQL failed: ' . $mysqli->error);
            }
        }

        $mysqli->close();
    }

    private static function env(string $name, string $default): string {
        $value = getenv($name);

        return ($value !== false && $value !== '') ? $value : $default;
    }
}
