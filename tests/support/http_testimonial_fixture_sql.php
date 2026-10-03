<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Testimonial rows written by HTTP storefront tests.
 */
final class http_testimonial_fixture_sql {

    public static function delete_testimonials_for_customer_id(int $customers_id): void {
        if ($customers_id <= 0) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        self::exec(
            $mysqli,
            'DELETE td FROM testimonials_description td'
            . ' INNER JOIN testimonials t ON t.testimonials_id = td.testimonials_id'
            . " WHERE t.customers_id = $customers_id"
        );
        self::exec($mysqli, "DELETE FROM testimonials WHERE customers_id = $customers_id");

        $mysqli->close();
    }

    public static function delete_testimonials_with_nick(string $customer_name): void {
        if ($customer_name === '') {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $escaped = $mysqli->real_escape_string($customer_name);
        self::exec(
            $mysqli,
            'DELETE td FROM testimonials_description td'
            . ' INNER JOIN testimonials t ON t.testimonials_id = td.testimonials_id'
            . " WHERE t.customers_name = '$escaped'"
        );
        self::exec($mysqli, "DELETE FROM testimonials WHERE customers_name = '$escaped'");

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

}
