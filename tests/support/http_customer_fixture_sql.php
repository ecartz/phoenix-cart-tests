<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Fixture customer row tweaks for HTTP storefront tests (password reset, addresses).
 */
final class http_customer_fixture_sql
{
    private const FIXTURE_CUSTOMER_ID = 1;

    private const SEED_PASSWORD_HASH = '$2y$12$yLp3Jl/6JtaqZru2oUgwnO5fL.t9i8ZwPKt3URtZqRJ52gST.G44.';

    public static function password_reset_key_for_fixture_customer(): ?string
    {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $statement = $mysqli->prepare(
            'SELECT password_reset_key FROM customers_info WHERE customers_info_id = ? LIMIT 1'
        );

        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $customer_id = self::FIXTURE_CUSTOMER_ID;
        $statement->bind_param('i', $customer_id);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result !== false ? $result->fetch_assoc() : false;
        $statement->close();
        $mysqli->close();

        if (!is_array($row) || $row['password_reset_key'] === null) {
            return null;
        }

        return (string) $row['password_reset_key'];
    }

    public static function restore_fixture_password_and_clear_reset_key(): void
    {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $hash = self::SEED_PASSWORD_HASH;
        $customer_id = self::FIXTURE_CUSTOMER_ID;

        $statement = $mysqli->prepare(
            'UPDATE customers SET customers_password = ? WHERE customers_id = ?'
        );
        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }
        $statement->bind_param('si', $hash, $customer_id);
        $statement->execute();
        $statement->close();

        self::exec(
            $mysqli,
            'UPDATE customers_info SET password_reset_key = NULL, password_reset_date = NULL'
            . ' WHERE customers_info_id = ' . self::FIXTURE_CUSTOMER_ID
        );

        $mysqli->close();
    }

    public static function clear_fixture_customer_basket(): void
    {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $customer_id = self::FIXTURE_CUSTOMER_ID;
        self::exec(
            $mysqli,
            'DELETE FROM customers_basket_attributes WHERE customers_id = ' . $customer_id
        );
        self::exec($mysqli, 'DELETE FROM customers_basket WHERE customers_id = ' . $customer_id);

        $mysqli->close();
    }

    public static function restore_fixture_firstname(): void
    {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        self::exec(
            $mysqli,
            "UPDATE customers SET customers_firstname = 'Fixture' WHERE customers_id = " . self::FIXTURE_CUSTOMER_ID
        );
        self::exec(
            $mysqli,
            "UPDATE address_book SET entry_firstname = 'Fixture' WHERE customers_id = "
            . self::FIXTURE_CUSTOMER_ID
            . ' AND address_book_id = 1'
        );

        $mysqli->close();
    }

    public static function latest_non_primary_address_book_id(): ?int
    {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $statement = $mysqli->prepare(
            'SELECT ab.address_book_id FROM address_book ab'
            . ' INNER JOIN customers c ON c.customers_id = ab.customers_id'
            . ' WHERE ab.customers_id = ? AND ab.address_book_id <> c.customers_default_address_id'
            . ' ORDER BY ab.address_book_id DESC LIMIT 1'
        );

        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $customer_id = self::FIXTURE_CUSTOMER_ID;
        $statement->bind_param('i', $customer_id);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result !== false ? $result->fetch_assoc() : false;
        $statement->close();
        $mysqli->close();

        if (!is_array($row)) {
            return null;
        }

        return (int) $row['address_book_id'];
    }

    public static function delete_address_book_entry(int $address_book_id): void
    {
        if ($address_book_id <= 0) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $statement = $mysqli->prepare(
            'DELETE FROM address_book WHERE address_book_id = ? AND customers_id = ?'
        );
        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $customer_id = self::FIXTURE_CUSTOMER_ID;
        $statement->bind_param('ii', $address_book_id, $customer_id);
        $statement->execute();
        $statement->close();
        $mysqli->close();
    }

    private static function connect(): \mysqli
    {
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

    private static function exec(\mysqli $mysqli, string $sql): void
    {
        if (!$mysqli->query($sql)) {
            throw new \RuntimeException('Query failed: ' . $mysqli->error . ' [' . $sql . ']');
        }
    }
}
