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

    /**
     * Inserts a recent successful action-recorder row so canPerform() returns false for the module.
     */
    public static function seed_recent_success(string $module, string $identifier): void {
        if ($module === '' || $identifier === '') {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $statement = $mysqli->prepare(
            'INSERT INTO action_recorder (module, user_id, user_name, identifier, success, date_added)
             VALUES (?, 0, ?, ?, 1, NOW())'
        );
        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('sss', $module, $identifier, $identifier);
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
