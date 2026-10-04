<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Minimal order rows for HTTP account-history and checkout-success edge cases.
 */
final class http_order_fixture_sql {

    /** @var array<int, string> */
    private static array $saved_date_purchased_by_orders_id = [];

    private const OTHER_CUSTOMER_ID = 9001;

    private const OTHER_CUSTOMER_EMAIL = 'phoenix-http-other@example.com';

    private const OTHER_ADDRESS_BOOK_ID = 9001;

    public static function insert_other_customer_order(): int {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        self::exec($mysqli, 'DELETE FROM orders_total WHERE orders_id IN (SELECT orders_id FROM orders WHERE customers_id = '
            . self::OTHER_CUSTOMER_ID . ')');
        self::exec($mysqli, 'DELETE FROM orders_status_history WHERE orders_id IN (SELECT orders_id FROM orders WHERE customers_id = '
            . self::OTHER_CUSTOMER_ID . ')');
        self::exec($mysqli, 'DELETE FROM orders_products WHERE orders_id IN (SELECT orders_id FROM orders WHERE customers_id = '
            . self::OTHER_CUSTOMER_ID . ')');
        self::exec($mysqli, 'DELETE FROM orders WHERE customers_id = ' . self::OTHER_CUSTOMER_ID);
        self::exec($mysqli, 'DELETE FROM address_book WHERE customers_id = ' . self::OTHER_CUSTOMER_ID);
        self::exec($mysqli, 'DELETE FROM customers_info WHERE customers_info_id = ' . self::OTHER_CUSTOMER_ID);
        self::exec($mysqli, 'DELETE FROM customers WHERE customers_id = ' . self::OTHER_CUSTOMER_ID);

        $hash = '$2y$12$yLp3Jl/6JtaqZru2oUgwnO5fL.t9i8ZwPKt3URtZqRJ52gST.G44.';
        self::exec(
            $mysqli,
            "INSERT INTO customers (customers_id, customers_gender, customers_firstname, customers_lastname,"
            . " customers_email_address, customers_default_address_id, customers_telephone, customers_password,"
            . " customers_newsletter, status) VALUES ("
            . self::OTHER_CUSTOMER_ID . ", 'm', 'Other', 'Customer', '" . self::OTHER_CUSTOMER_EMAIL . "', "
            . self::OTHER_ADDRESS_BOOK_ID . ", '555-0199', '" . $hash . "', '0', 1)"
        );
        self::exec(
            $mysqli,
            'INSERT INTO address_book (address_book_id, customers_id, entry_firstname, entry_lastname,'
            . ' entry_street_address, entry_postcode, entry_city, entry_state, entry_country_id, entry_zone_id)'
            . ' VALUES (' . self::OTHER_ADDRESS_BOOK_ID . ', ' . self::OTHER_CUSTOMER_ID
            . ", 'Other', 'Customer', '9 Other Street', '90210', 'Elsewhere', 'Florida', 223, 18)"
        );
        self::exec(
            $mysqli,
            'INSERT INTO customers_info (customers_info_id, customers_info_date_account_created,'
            . ' customers_info_date_account_last_modified, global_product_notifications)'
            . ' VALUES (' . self::OTHER_CUSTOMER_ID . ', NOW(), NOW(), 0)'
        );

        self::exec(
            $mysqli,
            "INSERT INTO orders (customers_id, customers_name, customers_street_address, customers_city,"
            . " customers_postcode, customers_state, customers_country, customers_country_id, customers_telephone,"
            . " customers_email_address, customers_address_format_id, delivery_name, delivery_street_address,"
            . " delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_country_id,"
            . " delivery_address_format_id, billing_name, billing_street_address, billing_city, billing_postcode,"
            . " billing_state, billing_country, billing_country_id, billing_address_format_id, payment_method,"
            . " date_purchased, orders_status, currency, currency_value) VALUES ("
            . self::OTHER_CUSTOMER_ID . ", 'Other Customer', '9 Other Street', 'Elsewhere', '90210', 'Florida',"
            . " 'United States', 223, '555-0199', '" . self::OTHER_CUSTOMER_EMAIL . "', 1,"
            . " 'Other Customer', '9 Other Street', 'Elsewhere', '90210', 'Florida', 'United States', 223, 1,"
            . " 'Other Customer', '9 Other Street', 'Elsewhere', '90210', 'Florida', 'United States', 223, 1,"
            . " 'Cash on Delivery', NOW(), 1, 'USD', 1.000000)"
        );

        $orders_id = (int) $mysqli->insert_id;
        self::exec(
            $mysqli,
            "INSERT INTO orders_status_history (orders_id, orders_status_id, date_added, customer_notified)"
            . " VALUES ($orders_id, 1, NOW(), 0)"
        );

        $mysqli->close();

        return $orders_id;
    }

    public static function delete_other_customer_fixture(): void {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        self::exec($mysqli, 'DELETE FROM orders_total WHERE orders_id IN (SELECT orders_id FROM orders WHERE customers_id = '
            . self::OTHER_CUSTOMER_ID . ')');
        self::exec($mysqli, 'DELETE FROM orders_status_history WHERE orders_id IN (SELECT orders_id FROM orders WHERE customers_id = '
            . self::OTHER_CUSTOMER_ID . ')');
        self::exec($mysqli, 'DELETE FROM orders_products WHERE orders_id IN (SELECT orders_id FROM orders WHERE customers_id = '
            . self::OTHER_CUSTOMER_ID . ')');
        self::exec($mysqli, 'DELETE FROM orders WHERE customers_id = ' . self::OTHER_CUSTOMER_ID);
        self::exec($mysqli, 'DELETE FROM address_book WHERE customers_id = ' . self::OTHER_CUSTOMER_ID);
        self::exec($mysqli, 'DELETE FROM customers_info WHERE customers_info_id = ' . self::OTHER_CUSTOMER_ID);
        self::exec($mysqli, 'DELETE FROM customers WHERE customers_id = ' . self::OTHER_CUSTOMER_ID);

        $mysqli->close();
    }

    public static function age_latest_order_for_email(string $email, int $minutes): void {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $lookup = $mysqli->prepare(
            'SELECT orders_id FROM orders WHERE customers_email_address = ? ORDER BY orders_id DESC LIMIT 1'
        );

        if ($lookup === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $lookup->bind_param('s', $email);
        $lookup->execute();
        $result = $lookup->get_result();
        $row = $result !== false ? $result->fetch_assoc() : false;
        $lookup->close();
        $mysqli->close();

        if (!is_array($row)) {
            throw new \RuntimeException('No order found to age for email: ' . $email);
        }

        self::remember_and_age_order_minutes((int) $row['orders_id'], $minutes);
    }

    public static function remember_and_age_order_minutes(int $orders_id, int $minutes): void {
        if ($orders_id <= 0) {
            throw new \InvalidArgumentException('orders_id must be positive.');
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $lookup = $mysqli->prepare(
            'SELECT customers_email_address FROM orders WHERE orders_id = ? LIMIT 1'
        );

        if ($lookup === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $lookup->bind_param('i', $orders_id);
        $lookup->execute();
        $result = $lookup->get_result();
        $row = $result !== false ? $result->fetch_assoc() : false;
        $lookup->close();
        $mysqli->close();

        if (!is_array($row)) {
            throw new \RuntimeException('No order found to age for orders_id: ' . $orders_id);
        }

        self::remember_and_age_orders_for_email((string) $row['customers_email_address'], $minutes);
    }

    public static function remember_and_age_orders_for_email(string $email, int $minutes): void {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $lookup = $mysqli->prepare(
            'SELECT orders_id, date_purchased FROM orders WHERE customers_email_address = ?'
        );

        if ($lookup === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $lookup->bind_param('s', $email);
        $lookup->execute();
        $result = $lookup->get_result();
        $rows = $result !== false ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $lookup->close();

        if ($rows === []) {
            $mysqli->close();
            throw new \RuntimeException('No orders found to age for email: ' . $email);
        }

        if (self::$saved_date_purchased_by_orders_id === []) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                self::$saved_date_purchased_by_orders_id[(int) $row['orders_id']] = (string) $row['date_purchased'];
            }
        }

        $statement = $mysqli->prepare(
            'UPDATE orders SET date_purchased = DATE_SUB(NOW(), INTERVAL ? MINUTE) WHERE customers_email_address = ?'
        );

        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('is', $minutes, $email);
        $statement->execute();
        $statement->close();
        $mysqli->close();
    }

    public static function restore_remembered_order_date_purchased(): void {
        if (self::$saved_date_purchased_by_orders_id === []) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $statement = $mysqli->prepare(
            'UPDATE orders SET date_purchased = ? WHERE orders_id = ?'
        );

        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        foreach (self::$saved_date_purchased_by_orders_id as $orders_id => $date_purchased) {
            $statement->bind_param('si', $date_purchased, $orders_id);
            $statement->execute();
        }

        $statement->close();
        $mysqli->close();

        self::$saved_date_purchased_by_orders_id = [];
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
