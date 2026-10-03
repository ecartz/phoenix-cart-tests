<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Copy harness payment module into the catalog and enable it for one HTTP test class.
 */
final class http_local_redirect_bootstrap {

    public const MODULE_FILENAME = 'http_local_redirect.php';

    public const MODULE_CODE = 'http_local_redirect';

    public const TOKEN_INPUT_NAME = 'http_local_redirect_token';

    public const TOKEN_VALUE = 'phoenix-cart-tests-local-redirect-token';

    public const RETURN_SCRIPT_FILENAME = 'return.php';

    public const PAYMENT_METHOD_TITLE = 'HTTP Local Redirect Fixture';

    private static ?string $saved_installed = null;

    public static function install_fixture_module(): void {
        $repo_root = dirname(__DIR__, 2);
        $module_source = $repo_root . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'http'
            . DIRECTORY_SEPARATOR . self::MODULE_FILENAME;
        $lang_source = $repo_root . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'http'
            . DIRECTORY_SEPARATOR . 'http_local_redirect.lang.php';
        $return_source = $repo_root . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'http'
            . DIRECTORY_SEPARATOR . 'http_local_redirect_return.php';

        $payment_dir = http_bootstrap::catalog_root() . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
            . 'modules' . DIRECTORY_SEPARATOR . 'payment';
        $lang_dir = http_bootstrap::catalog_root() . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
            . 'languages' . DIRECTORY_SEPARATOR . 'english' . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . 'payment';

        if (!is_dir($payment_dir)) {
            throw new \RuntimeException('Catalog payment directory missing: ' . $payment_dir);
        }

        if (!is_dir($lang_dir) && !mkdir($lang_dir, 0775, true) && !is_dir($lang_dir)) {
            throw new \RuntimeException('Cannot create catalog payment language directory: ' . $lang_dir);
        }

        if (!copy($module_source, $payment_dir . DIRECTORY_SEPARATOR . self::MODULE_FILENAME)) {
            throw new \RuntimeException('Failed to copy fixture payment module into catalog.');
        }

        if (!copy($lang_source, $lang_dir . DIRECTORY_SEPARATOR . self::MODULE_FILENAME)) {
            self::remove_catalog_files($payment_dir, $lang_dir);
            throw new \RuntimeException('Failed to copy fixture payment language into catalog.');
        }

        $ext_dir = http_bootstrap::catalog_root() . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . 'payment' . DIRECTORY_SEPARATOR . self::MODULE_CODE;
        if (!is_dir($ext_dir) && !mkdir($ext_dir, 0775, true) && !is_dir($ext_dir)) {
            self::remove_catalog_files($payment_dir, $lang_dir);
            throw new \RuntimeException('Cannot create catalog ext payment directory: ' . $ext_dir);
        }

        if (!copy($return_source, $ext_dir . DIRECTORY_SEPARATOR . self::RETURN_SCRIPT_FILENAME)) {
            self::remove_catalog_files($payment_dir, $lang_dir, $ext_dir);
            throw new \RuntimeException('Failed to copy fixture ext return script into catalog.');
        }

        mysql_bootstrap::define_connection_constants();

        $mysqli = self::connect();

        self::$saved_installed = self::fetch_configuration_value_via($mysqli, 'MODULE_PAYMENT_INSTALLED')
            ?? 'cod.php;moneyorder.php';

        self::upsert_configuration($mysqli, 'MODULE_PAYMENT_INSTALLED', self::append_module(self::$saved_installed));
        self::upsert_configuration($mysqli, 'MODULE_PAYMENT_HTTP_LOCAL_REDIRECT_STATUS', 'True');
        self::upsert_configuration($mysqli, 'MODULE_PAYMENT_HTTP_LOCAL_REDIRECT_SORT_ORDER', '0');

        $mysqli->close();
    }

    public static function remove_fixture_module(): void {
        $payment_dir = http_bootstrap::catalog_root() . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
            . 'modules' . DIRECTORY_SEPARATOR . 'payment';
        $lang_dir = http_bootstrap::catalog_root() . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
            . 'languages' . DIRECTORY_SEPARATOR . 'english' . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . 'payment';
        $ext_dir = http_bootstrap::catalog_root() . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . 'payment' . DIRECTORY_SEPARATOR . self::MODULE_CODE;

        self::remove_catalog_files($payment_dir, $lang_dir, $ext_dir);

        if (self::$saved_installed === null) {
            return;
        }

        mysql_bootstrap::define_connection_constants();

        $mysqli = self::connect();
        self::upsert_configuration($mysqli, 'MODULE_PAYMENT_INSTALLED', self::$saved_installed);
        $mysqli->close();

        self::$saved_installed = null;
    }

    private static function append_module(string $installed_list): string {
        $modules = array_filter(array_map('trim', explode(';', $installed_list)));

        if (!in_array(self::MODULE_FILENAME, $modules, true)) {
            $modules[] = self::MODULE_FILENAME;
        }

        return implode(';', $modules);
    }

    public static function ext_return_path(): string {
        return '/ext/modules/payment/' . self::MODULE_CODE . '/' . self::RETURN_SCRIPT_FILENAME;
    }

    private static function remove_catalog_files(string $payment_dir, string $lang_dir, ?string $ext_dir = null): void {
        $module_path = $payment_dir . DIRECTORY_SEPARATOR . self::MODULE_FILENAME;
        if (is_file($module_path)) {
            @unlink($module_path);
        }

        $lang_path = $lang_dir . DIRECTORY_SEPARATOR . self::MODULE_FILENAME;
        if (is_file($lang_path)) {
            @unlink($lang_path);
        }

        if ($ext_dir !== null) {
            $return_path = $ext_dir . DIRECTORY_SEPARATOR . self::RETURN_SCRIPT_FILENAME;
            if (is_file($return_path)) {
                @unlink($return_path);
            }
            if (is_dir($ext_dir)) {
                @rmdir($ext_dir);
            }
        }
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

    private static function fetch_configuration_value_via(\mysqli $mysqli, string $configuration_key): ?string {
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
) VALUES (?, ?, ?, 'phoenix-cart-tests HTTP local redirect fixture', 6, 0, NOW())
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
