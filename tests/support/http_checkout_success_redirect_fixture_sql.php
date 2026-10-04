<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Adjust redirect-old-order configuration and order timestamps for HTTP checkout_success tests.
 */
final class http_checkout_success_redirect_fixture_sql {

    private static bool $active = false;

    private static ?string $saved_minutes = null;

    public static function enable_redirect_after_minutes(int $minutes): void {
        if (self::$active) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        self::$saved_minutes = self::fetch_configuration_value(
            $mysqli,
            'MODULE_CONTENT_CHECKOUT_SUCCESS_REDIRECT_OLD_ORDER_MINUTES'
        );

        self::set_configuration(
            $mysqli,
            'MODULE_CONTENT_CHECKOUT_SUCCESS_REDIRECT_OLD_ORDER_MINUTES',
            (string) max(1, $minutes)
        );
        self::set_configuration(
            $mysqli,
            'MODULE_CONTENT_CHECKOUT_SUCCESS_REDIRECT_OLD_ORDER_STATUS',
            'True'
        );

        self::$active = true;
        $mysqli->close();
    }

    public static function backdate_all_orders_for_email(string $customers_email_address, int $minutes_ago): void {
        if ($customers_email_address === '' || $minutes_ago <= 0) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $statement = $mysqli->prepare(
            'UPDATE orders SET date_purchased = DATE_SUB(NOW(), INTERVAL ? MINUTE)'
            . ' WHERE customers_email_address = ?'
        );
        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('is', $minutes_ago, $customers_email_address);
        $statement->execute();
        $statement->close();
        $mysqli->close();
    }

    public static function backdate_order(int $orders_id, int $minutes_ago): void {
        if ($orders_id <= 0 || $minutes_ago <= 0) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $statement = $mysqli->prepare(
            'UPDATE orders SET date_purchased = DATE_SUB(NOW(), INTERVAL ? MINUTE) WHERE orders_id = ?'
        );
        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('ii', $minutes_ago, $orders_id);
        $statement->execute();
        $statement->close();
        $mysqli->close();
    }

    public static function restore(): void {
        if (!self::$active) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        if (self::$saved_minutes !== null) {
            self::set_configuration(
                $mysqli,
                'MODULE_CONTENT_CHECKOUT_SUCCESS_REDIRECT_OLD_ORDER_MINUTES',
                self::$saved_minutes
            );
        }

        self::$active = false;
        self::$saved_minutes = null;
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
        $statement->close();
    }

}
