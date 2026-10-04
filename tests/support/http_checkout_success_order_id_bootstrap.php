<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Copy harness checkout_success order_id shim into the catalog for redirect HTTP tests.
 */
final class http_checkout_success_order_id_bootstrap {

    public const HOOK_FILENAME = 'http_checkout_success_order_id_hook.php';

    public const CONFIG_KEY = 'HTTP_TEST_CHECKOUT_SUCCESS_ORDER_ID_SHIM_STATUS';

    private static ?string $saved_status = null;

    public static function install_fixture_hook(): void {
        $repo_root = dirname(__DIR__, 2);
        $hook_source = $repo_root . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'http'
            . DIRECTORY_SEPARATOR . self::HOOK_FILENAME;

        $hook_dir = http_bootstrap::catalog_root() . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
            . 'hooks' . DIRECTORY_SEPARATOR . 'shop' . DIRECTORY_SEPARATOR . 'siteWide';

        if (!is_dir($hook_dir) && !mkdir($hook_dir, 0775, true) && !is_dir($hook_dir)) {
            throw new \RuntimeException('Cannot create catalog hook directory: ' . $hook_dir);
        }

        if (!copy($hook_source, $hook_dir . DIRECTORY_SEPARATOR . self::HOOK_FILENAME)) {
            throw new \RuntimeException('Failed to copy checkout_success order_id hook into catalog.');
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        if (self::$saved_status === null) {
            self::$saved_status = self::fetch_configuration_value($mysqli, self::CONFIG_KEY);
        }

        self::upsert_configuration($mysqli, self::CONFIG_KEY, 'True');
        $mysqli->close();
    }

    public static function remove_fixture_hook(): void {
        $hook_path = http_bootstrap::catalog_root() . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
            . 'hooks' . DIRECTORY_SEPARATOR . 'shop' . DIRECTORY_SEPARATOR . 'siteWide' . DIRECTORY_SEPARATOR
            . self::HOOK_FILENAME;

        if (is_file($hook_path)) {
            @unlink($hook_path);
        }

        if (self::$saved_status === null) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        if (self::$saved_status === '') {
            $key = self::CONFIG_KEY;
            $statement = $mysqli->prepare('DELETE FROM configuration WHERE configuration_key = ?');
            if ($statement !== false) {
                $statement->bind_param('s', $key);
                $statement->execute();
                $statement->close();
            }
        } else {
            self::upsert_configuration($mysqli, self::CONFIG_KEY, self::$saved_status);
        }

        $mysqli->close();
        self::$saved_status = null;
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
            return '';
        }

        return (string) $row['configuration_value'];
    }

    private static function upsert_configuration(
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

        $title = $configuration_key;
        $insert = $mysqli->prepare(
            'INSERT INTO configuration (configuration_title, configuration_key, configuration_value,'
            . ' configuration_description, configuration_group_id, sort_order, date_added)'
            . ' VALUES (?, ?, ?, ?, 6, 0, NOW())'
        );
        if ($insert === false) {
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $description = 'Harness-only configuration for HTTP tests.';
        $insert->bind_param('ssss', $title, $configuration_key, $configuration_value, $description);
        $insert->execute();
        $insert->close();
    }

}
