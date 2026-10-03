<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Wave 6 part 3 — inject Stripe SCA test keys from env (CI secrets), never committed.
 */
final class payment_sandbox_bootstrap {

    private const PUBLISHABLE_ENV = 'PHOENIX_STRIPE_SCA_TEST_PUBLISHABLE_KEY';

    private const SECRET_ENV = 'PHOENIX_STRIPE_SCA_TEST_SECRET_KEY';

    public static function is_enabled(): bool {
        $flag = getenv('PHOENIX_PAYMENT_SANDBOX_ENABLED');

        if ($flag === false || $flag === '' || $flag === '0') {
            return false;
        }

        return self::publishable_key() !== '' && self::secret_key() !== '';
    }

    public static function apply_to_database(): void {
        if (!self::is_enabled()) {
            throw new \RuntimeException('Payment sandbox env is not configured.');
        }

        mysql_bootstrap::define_connection_constants();

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

        self::append_payment_module($mysqli, 'stripe_sca.php');
        self::upsert_configuration($mysqli, 'MODULE_PAYMENT_STRIPE_SCA_STATUS', 'True');
        self::upsert_configuration($mysqli, 'MODULE_PAYMENT_STRIPE_SCA_TRANSACTION_SERVER', 'Test');
        self::upsert_configuration(
            $mysqli,
            'MODULE_PAYMENT_STRIPE_SCA_TEST_PUBLISHABLE_KEY',
            self::publishable_key()
        );
        self::upsert_configuration($mysqli, 'MODULE_PAYMENT_STRIPE_SCA_TEST_SECRET_KEY', self::secret_key());

        $mysqli->close();
    }

    public static function fetch_configuration_value(string $configuration_key): ?string {
        mysql_bootstrap::define_connection_constants();

        $mysqli = new \mysqli(
            (string) DB_SERVER,
            (string) DB_SERVER_USERNAME,
            (string) DB_SERVER_PASSWORD,
            (string) DB_DATABASE
        );

        if ($mysqli->connect_errno) {
            throw new \RuntimeException('MySQL connect failed: ' . $mysqli->connect_error);
        }

        $statement = $mysqli->prepare(
            'SELECT configuration_value FROM configuration WHERE configuration_key = ? LIMIT 1'
        );

        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('s', $configuration_key);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result !== false ? $result->fetch_assoc() : false;
        $statement->close();
        $mysqli->close();

        if (!is_array($row)) {
            return null;
        }

        return (string) $row['configuration_value'];
    }

    private static function publishable_key(): string {
        return self::env(self::PUBLISHABLE_ENV);
    }

    private static function secret_key(): string {
        return self::env(self::SECRET_ENV);
    }

    private static function env(string $name): string {
        $value = getenv($name);

        return ($value !== false && $value !== '') ? $value : '';
    }

    private static function append_payment_module(\mysqli $mysqli, string $module_filename): void {
        $key = 'MODULE_PAYMENT_INSTALLED';
        $current = self::fetch_configuration_value_via($mysqli, $key) ?? 'cod.php;moneyorder.php';
        $modules = array_filter(array_map('trim', explode(';', $current)));

        if (!in_array($module_filename, $modules, true)) {
            $modules[] = $module_filename;
        }

        self::upsert_configuration($mysqli, $key, implode(';', $modules));
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
) VALUES (?, ?, ?, 'phoenix-cart-tests payment sandbox', 6, 0, NOW())
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
