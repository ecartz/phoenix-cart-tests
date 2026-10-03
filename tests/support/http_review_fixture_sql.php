<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Catalog configuration and review rows for HTTP product review tests.
 */
final class http_review_fixture_sql {

    private const FIXTURE_CUSTOMER_ID = 1;

    private static ?string $saved_allow_all_reviews = null;

    public static function enable_allow_all_reviews(): void {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        if (self::$saved_allow_all_reviews === null) {
            self::$saved_allow_all_reviews = self::fetch_configuration_value($mysqli, 'ALLOW_ALL_REVIEWS') ?? 'false';
        }

        self::set_configuration($mysqli, 'ALLOW_ALL_REVIEWS', 'true');
        $mysqli->close();
    }

    public static function restore_allow_all_reviews(): void {
        if (self::$saved_allow_all_reviews === null) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();
        self::set_configuration($mysqli, 'ALLOW_ALL_REVIEWS', self::$saved_allow_all_reviews);
        $mysqli->close();
        self::$saved_allow_all_reviews = null;
    }

    public static function delete_reviews_for_fixture_customer_product(int $products_id): void {
        if ($products_id <= 0) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $customer_id = self::FIXTURE_CUSTOMER_ID;
        self::exec(
            $mysqli,
            'DELETE rd FROM reviews_description rd'
            . ' INNER JOIN reviews r ON r.reviews_id = rd.reviews_id'
            . " WHERE r.customers_id = $customer_id AND r.products_id = $products_id"
        );
        self::exec(
            $mysqli,
            "DELETE FROM reviews WHERE customers_id = $customer_id AND products_id = $products_id"
        );

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

    private static function exec(\mysqli $mysqli, string $sql): void {
        if (!$mysqli->query($sql)) {
            throw new \RuntimeException('Query failed: ' . $mysqli->error . ' [' . $sql . ']');
        }
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
