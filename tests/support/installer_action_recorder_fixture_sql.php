<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Clears action-recorder rows on the disposable installer database between storefront form tests.
 */
final class installer_action_recorder_fixture_sql {

    public static function clear_module(string $module): void {
        if ($module === '') {
            return;
        }

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
            installer_bootstrap::db_host(),
            installer_bootstrap::db_user(),
            installer_bootstrap::db_password(),
            installer_bootstrap::installer_db_name()
        );

        if ($mysqli->connect_errno) {
            throw new \RuntimeException('MySQL connect failed: ' . $mysqli->connect_error);
        }

        $mysqli->set_charset('utf8mb4');

        return $mysqli;
    }

}
