<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Testimonial rows written during HTTP acceptance tests.
 */
final class http_testimonial_fixture_sql {

    private const FIXTURE_CUSTOMER_ID = 1;

    public static function delete_fixture_customer_testimonials(): void {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $customer_id = self::FIXTURE_CUSTOMER_ID;
        self::exec(
            $mysqli,
            'DELETE td FROM testimonials_description td'
            . ' INNER JOIN testimonials t ON t.testimonials_id = td.testimonials_id'
            . " WHERE t.customers_id = $customer_id"
        );
        self::exec($mysqli, "DELETE FROM testimonials WHERE customers_id = $customer_id");

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
