<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Second customer + order for account history access-control HTTP tests.
 */
final class http_account_history_fixture_sql {

    private const OTHER_CUSTOMER_ID = 90001;

    private const OTHER_ADDRESS_BOOK_ID = 90001;

    private const OTHER_EMAIL = 'phoenix-http-other-order@example.com';

    private static ?int $other_orders_id = null;

    public static function other_customer_order_id(): int {
        if (self::$other_orders_id === null) {
            self::insert_other_customer_order();
        }

        return (int) self::$other_orders_id;
    }

    public static function insert_other_customer_order(): void {
        self::remove_other_customer_order();

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $password_hash = '$2y$12$yLp3Jl/6JtaqZru2oUgwnO5fL.t9i8ZwPKt3URtZqRJ52gST.G44.';

        self::exec(
            $mysqli,
            'INSERT INTO customers (customers_id, customers_gender, customers_firstname, customers_lastname,'
            . ' customers_dob, customers_email_address, customers_default_address_id, customers_telephone,'
            . " customers_fax, customers_password, customers_newsletter, status) VALUES ("
            . self::OTHER_CUSTOMER_ID . ", 'm', 'Other', 'Customer', NULL, '"
            . self::OTHER_EMAIL . "', " . self::OTHER_ADDRESS_BOOK_ID . ", '555-0199', NULL, '"
            . $password_hash . "', '0', 1)"
        );

        self::exec(
            $mysqli,
            'INSERT INTO address_book (address_book_id, customers_id, entry_gender, entry_company,'
            . ' entry_firstname, entry_lastname, entry_street_address, entry_suburb, entry_postcode,'
            . " entry_city, entry_state, entry_country_id, entry_zone_id) VALUES ("
            . self::OTHER_ADDRESS_BOOK_ID . ', ' . self::OTHER_CUSTOMER_ID
            . ", 'm', '', 'Other', 'Customer', '9 Other Street', '', '90210', 'Elsewhere', 'Florida', 223, 18)"
        );

        self::exec(
            $mysqli,
            'INSERT INTO customers_info (customers_info_id, customers_info_date_of_last_logon,'
            . ' customers_info_number_of_logons, customers_info_date_account_created,'
            . ' customers_info_date_account_last_modified, global_product_notifications,'
            . ' password_reset_key, password_reset_date) VALUES ('
            . self::OTHER_CUSTOMER_ID . ', NULL, 0, NOW(), NOW(), 0, NULL, NULL)'
        );

        self::exec(
            $mysqli,
            "INSERT INTO orders (customers_id, customers_name, customers_company, customers_street_address,"
            . " customers_suburb, customers_city, customers_postcode, customers_state, customers_country,"
            . " customers_country_id, customers_telephone, customers_email_address, customers_address_format_id,"
            . " delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city,"
            . " delivery_postcode, delivery_state, delivery_country, delivery_country_id, delivery_address_format_id,"
            . " billing_name, billing_company, billing_street_address, billing_suburb, billing_city,"
            . " billing_postcode, billing_state, billing_country, billing_country_id, billing_address_format_id,"
            . " payment_method, date_purchased, orders_status, currency, currency_value) VALUES ("
            . self::OTHER_CUSTOMER_ID . ", 'Other Customer', '', '9 Other Street', '', 'Elsewhere', '90210',"
            . " 'Florida', 'United States', 223, '555-0199', '" . self::OTHER_EMAIL . "', 1,"
            . " 'Other Customer', '', '9 Other Street', '', 'Elsewhere', '90210', 'Florida',"
            . " 'United States', 223, 1, 'Other Customer', '', '9 Other Street', '', 'Elsewhere', '90210',"
            . " 'Florida', 'United States', 223, 1, 'Cash on Delivery', NOW(), 1, 'USD', 1.0000)"
        );

        self::$other_orders_id = (int) $mysqli->insert_id;

        $orders_id = self::$other_orders_id;
        self::exec(
            $mysqli,
            'INSERT INTO orders_products (orders_id, products_id, products_model, products_name,'
            . " products_price, final_price, products_tax, products_quantity) VALUES ($orders_id, 3, 'PEA-1',"
            . " 'Pears', 4.9900, 4.9900, 0.0000, 1)"
        );

        $mysqli->close();
    }

    public static function remove_other_customer_order(): void {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $customer_id = self::OTHER_CUSTOMER_ID;
        self::exec($mysqli, 'DELETE FROM orders_products WHERE orders_id IN (SELECT orders_id FROM orders WHERE customers_id = ' . $customer_id . ')');
        self::exec($mysqli, 'DELETE FROM orders_total WHERE orders_id IN (SELECT orders_id FROM orders WHERE customers_id = ' . $customer_id . ')');
        self::exec($mysqli, 'DELETE FROM orders_status_history WHERE orders_id IN (SELECT orders_id FROM orders WHERE customers_id = ' . $customer_id . ')');
        self::exec($mysqli, 'DELETE FROM orders WHERE customers_id = ' . $customer_id);
        self::exec($mysqli, 'DELETE FROM address_book WHERE customers_id = ' . $customer_id);
        self::exec($mysqli, 'DELETE FROM customers_info WHERE customers_info_id = ' . $customer_id);
        self::exec($mysqli, 'DELETE FROM customers WHERE customers_id = ' . $customer_id);

        $mysqli->close();
        self::$other_orders_id = null;
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
