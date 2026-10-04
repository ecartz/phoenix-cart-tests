<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Clears action-recorder rows on the disposable installer database.
 */
final class installer_action_recorder_fixture_sql {

    public static function clear_module(string $module): void {
        if ($module === '') {
            return;
        }

        mysql_bootstrap::define_connection_constants();

        $database = getenv('PHOENIX_INSTALLER_DB_NAME');
        if (!is_string($database) || $database === '') {
            $database = 'phoenix_install';
        }

        $mysqli = new \mysqli(
            (string) (getenv('PHOENIX_DB_HOST') ?: '127.0.0.1'),
            (string) (getenv('PHOENIX_DB_USER') ?: 'phoenix'),
            (string) (getenv('PHOENIX_DB_PASSWORD') ?: 'phoenix'),
            $database
        );

        if ($mysqli->connect_errno) {
            return;
        }

        $mysqli->set_charset('utf8mb4');

        $statement = $mysqli->prepare('DELETE FROM action_recorder WHERE module = ?');
        if ($statement !== false) {
            $statement->bind_param('s', $module);
            $statement->execute();
            $statement->close();
        }

        $mysqli->close();
    }

}
