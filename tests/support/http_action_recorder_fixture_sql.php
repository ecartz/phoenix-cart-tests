<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Clears action-recorder rows that throttle storefront forms in HTTP tests.
 */
final class http_action_recorder_fixture_sql {

    public static function clear_modules(array $modules): void {
        if ($modules === []) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $escaped = array_map(static fn (string $module): string => "'" . $mysqli->real_escape_string($module) . "'", $modules);
        $sql = 'DELETE FROM action_recorder WHERE module IN (' . implode(',', $escaped) . ')';

        if (!$mysqli->query($sql)) {
            throw new \RuntimeException('Query failed: ' . $mysqli->error . ' [' . $sql . ']');
        }

        $mysqli->close();
    }

    private static function connect(): \mysqli {
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

        return $mysqli;
    }

}
