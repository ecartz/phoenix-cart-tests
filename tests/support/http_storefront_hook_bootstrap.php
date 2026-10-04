<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Copy harness storefront hook into the catalog and toggle it via configuration.
 */
final class http_storefront_hook_bootstrap {

    public const HOOK_FILENAME = 'http_storefront_hook_marker.php';

    public const MARKER_HTML = '<!-- phoenix-cart-tests-storefront-hook-marker -->';

    public const CONFIG_KEY = 'HTTP_TEST_STOREFRONT_HOOK_MARKER_STATUS';

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
            throw new \RuntimeException('Failed to copy storefront hook fixture into catalog.');
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        if (self::$saved_status === null) {
            self::$saved_status = self::fetch_configuration_value($mysqli, self::CONFIG_KEY);
        }

        self::upsert_configuration($mysqli, self::CONFIG_KEY, 'True');
        $mysqli->close();
    }

    public static function set_marker_enabled(bool $enabled): void {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();
        self::upsert_configuration($mysqli, self::CONFIG_KEY, $enabled ? 'True' : 'False');
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
            <<<'EOSQL'
INSERT INTO configuration (
  configuration_title,
  configuration_key,
  configuration_value,
  configuration_description,
  configuration_group_id,
  sort_order,
  date_added
) VALUES (?, ?, ?, 'phoenix-cart-tests HTTP storefront hook fixture', 6, 0, NOW())
ON DUPLICATE KEY UPDATE configuration_value = VALUES(configuration_value)
EOSQL
        );

        if ($statement === false) {
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $title = $configuration_key;
        $statement->bind_param('sss', $title, $configuration_key, $configuration_value);
        $statement->execute();
        $statement->close();
    }

}
