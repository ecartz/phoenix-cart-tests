<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Turns the password-forgotten notification on for one HTTP test.
 *
 * The sample shop installs checkout and create-account mail only.
 */
final class http_notification_fixture_sql {

    private static bool $active = false;

    private static ?string $saved_installed = null;

    private static ?string $saved_status = null;

    public static function enable_password_forgotten(): void {
        if (self::$active) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        self::$saved_installed = self::fetch_configuration_value($mysqli, 'MODULE_NOTIFICATIONS_INSTALLED') ?? '';
        self::$saved_status = self::fetch_configuration_value(
            $mysqli,
            'MODULE_NOTIFICATIONS_PASSWORD_FORGOTTEN_STATUS'
        );

        $installed = self::$saved_installed;
        if (!str_contains($installed, 'n_password_forgotten.php')) {
            $installed = $installed === ''
                ? 'n_password_forgotten.php'
                : $installed . ';n_password_forgotten.php';
            self::set_configuration($mysqli, 'MODULE_NOTIFICATIONS_INSTALLED', $installed);
        }

        if (self::$saved_status !== 'True') {
            self::set_configuration($mysqli, 'MODULE_NOTIFICATIONS_PASSWORD_FORGOTTEN_STATUS', 'True');
        }

        self::$active = true;
        $mysqli->close();
    }

    public static function restore_password_forgotten(): void {
        if (!self::$active) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        self::set_configuration(
            $mysqli,
            'MODULE_NOTIFICATIONS_INSTALLED',
            self::$saved_installed ?? ''
        );

        if (self::$saved_status === null) {
            self::delete_configuration($mysqli, 'MODULE_NOTIFICATIONS_PASSWORD_FORGOTTEN_STATUS');
        } else {
            self::set_configuration(
                $mysqli,
                'MODULE_NOTIFICATIONS_PASSWORD_FORGOTTEN_STATUS',
                self::$saved_status
            );
        }

        self::$active = false;
        self::$saved_installed = null;
        self::$saved_status = null;
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

    private static function fetch_configuration_value(\mysqli $mysqli, string $configuration_key): ?string {
        $statement = $mysqli->prepare(
            'SELECT configuration_value FROM configuration WHERE configuration_key = ? LIMIT 1'
        );

        if ($statement === false) {
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('s', $configuration_key);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result !== false ? $result->fetch_assoc() : false;
        $statement->close();

        if (!is_array($row)) {
            return null;
        }

        return (string) $row['configuration_value'];
    }

    private static function set_configuration(
        \mysqli $mysqli,
        string $configuration_key,
        string $configuration_value
    ): void {
        $statement = $mysqli->prepare(
            'UPDATE configuration SET configuration_value = ? WHERE configuration_key = ?'
        );

        if ($statement === false) {
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('ss', $configuration_value, $configuration_key);
        $statement->execute();
        $affected = $statement->affected_rows;
        $statement->close();

        if ($affected > 0) {
            return;
        }

        $existing = self::fetch_configuration_value($mysqli, $configuration_key);
        if ($existing !== null) {
            return;
        }

        $title = $configuration_key;
        $description = 'HTTP notification fixture';
        $group_id = 6;
        $sort_order = 0;
        $insert = $mysqli->prepare(
            'INSERT INTO configuration (configuration_title, configuration_key, configuration_value,'
            . ' configuration_description, configuration_group_id, sort_order, date_added)'
            . ' VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );

        if ($insert === false) {
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $insert->bind_param(
            'ssssii',
            $title,
            $configuration_key,
            $configuration_value,
            $description,
            $group_id,
            $sort_order
        );
        $insert->execute();
        $insert->close();
    }

    private static function delete_configuration(\mysqli $mysqli, string $configuration_key): void {
        $statement = $mysqli->prepare('DELETE FROM configuration WHERE configuration_key = ?');

        if ($statement === false) {
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('s', $configuration_key);
        $statement->execute();
        $statement->close();
    }

}
