<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Clears action-recorder rows that throttle HTTP storefront forms between tests.
 */
final class http_action_recorder_fixture_sql {

    public static function clear_module(string $module): void {
        if ($module === '') {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $statement = $mysqli->prepare('DELETE FROM action_recorder WHERE module = ?');
        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('s', $module);
        $statement->execute();
        $statement->close();
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
