<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Temporary catalog rows for HTTP checkout edge tests (virtual download, COD geo zone).
 */
final class http_checkout_fixture_sql {

    private const VIRTUAL_OPTION_ID = 90100;

    private const VIRTUAL_VALUE_ID = 90100;

    private const EXCLUDING_GEO_ZONE_ID = 90100;

    private const CART_OPTION_ID = 90201;

    private const CART_VALUE_ID = 90201;

    private const PRICED_OPTION_ID = 90202;

    private const PRICED_VALUE_ID = 90202;

    private static ?int $virtual_products_attributes_id = null;

    private static ?int $cart_products_attributes_id = null;

    private static ?int $priced_products_attributes_id = null;

    private static ?string $saved_cod_zone = null;

    private static ?string $saved_flat_zone = null;

    private static ?string $saved_shipping_installed = null;

    private static ?string $saved_free_shipping = null;

    private static ?string $saved_free_shipping_over = null;

    private static ?string $saved_stock_allow_checkout = null;

    private static ?int $saved_pears_quantity = null;

    private static ?string $saved_display_price_with_tax = null;

    private static ?string $saved_flat_shipping_tax_class = null;

    public static function insert_virtual_download_for_pears(): void {
        self::remove_virtual_download_for_pears();

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        self::exec(
            $mysqli,
            'INSERT INTO products_options (products_options_id, language_id, products_options_name, sort_order)'
            . " VALUES (" . self::VIRTUAL_OPTION_ID . ", 1, 'HTTP Test Download', 99)"
        );
        self::exec(
            $mysqli,
            'INSERT INTO products_options_values (products_options_values_id, language_id, products_options_values_name, sort_order)'
            . " VALUES (" . self::VIRTUAL_VALUE_ID . ", 1, 'http-test-download.zip', 99)"
        );
        self::exec(
            $mysqli,
            'INSERT INTO products_options_values_to_products_options (products_options_id, products_options_values_id)'
            . ' VALUES (' . self::VIRTUAL_OPTION_ID . ', ' . self::VIRTUAL_VALUE_ID . ')'
        );
        self::exec(
            $mysqli,
            'INSERT INTO products_attributes (products_id, options_id, options_values_id, options_values_price, price_prefix)'
            . ' VALUES (3, ' . self::VIRTUAL_OPTION_ID . ', ' . self::VIRTUAL_VALUE_ID . ", '0.0000', '+')"
        );

        self::$virtual_products_attributes_id = (int) $mysqli->insert_id;

        $attributes_id = self::$virtual_products_attributes_id;
        self::exec(
            $mysqli,
            'INSERT INTO products_attributes_download (products_attributes_id, products_attributes_filename,'
            . ' products_attributes_maxdays, products_attributes_maxcount)'
            . " VALUES ($attributes_id, 'http-test-download.zip', 7, 5)"
        );

        $mysqli->close();
    }

    public static function remove_virtual_download_for_pears(): void {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        if (self::$virtual_products_attributes_id !== null) {
            $attributes_id = self::$virtual_products_attributes_id;
            self::exec(
                $mysqli,
                "DELETE FROM products_attributes_download WHERE products_attributes_id = $attributes_id"
            );
            self::exec(
                $mysqli,
                "DELETE FROM products_attributes WHERE products_attributes_id = $attributes_id"
            );
        } else {
            self::exec(
                $mysqli,
                'DELETE FROM products_attributes_download WHERE products_attributes_id IN ('
                . 'SELECT products_attributes_id FROM products_attributes WHERE products_id = 3 AND options_id = '
                . self::VIRTUAL_OPTION_ID . ')'
            );
            self::exec(
                $mysqli,
                'DELETE FROM products_attributes WHERE products_id = 3 AND options_id = ' . self::VIRTUAL_OPTION_ID
            );
        }
        self::exec(
            $mysqli,
            'DELETE FROM products_options_values_to_products_options WHERE products_options_id = '
            . self::VIRTUAL_OPTION_ID
        );
        self::exec(
            $mysqli,
            'DELETE FROM products_options_values WHERE products_options_values_id = ' . self::VIRTUAL_VALUE_ID
        );
        self::exec(
            $mysqli,
            'DELETE FROM products_options WHERE products_options_id = ' . self::VIRTUAL_OPTION_ID
        );

        $mysqli->close();
        self::$virtual_products_attributes_id = null;
    }

    public static function virtual_option_id(): int {
        return self::VIRTUAL_OPTION_ID;
    }

    public static function virtual_value_id(): int {
        return self::VIRTUAL_VALUE_ID;
    }

    public static function insert_cart_attribute_for_pears(): void {
        self::remove_cart_attribute_for_pears();

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        self::exec(
            $mysqli,
            'INSERT INTO products_options (products_options_id, language_id, products_options_name, sort_order)'
            . " VALUES (" . self::CART_OPTION_ID . ", 1, 'HTTP Test Cart Option', 98)"
        );
        self::exec(
            $mysqli,
            'INSERT INTO products_options_values (products_options_values_id, language_id, products_options_values_name, sort_order)'
            . " VALUES (" . self::CART_VALUE_ID . ", 1, 'HTTP Cart Red', 98)"
        );
        self::exec(
            $mysqli,
            'INSERT INTO products_options_values_to_products_options (products_options_id, products_options_values_id)'
            . ' VALUES (' . self::CART_OPTION_ID . ', ' . self::CART_VALUE_ID . ')'
        );
        self::exec(
            $mysqli,
            'INSERT INTO products_attributes (products_id, options_id, options_values_id, options_values_price, price_prefix)'
            . ' VALUES (3, ' . self::CART_OPTION_ID . ', ' . self::CART_VALUE_ID . ", '0.0000', '+')"
        );

        self::$cart_products_attributes_id = (int) $mysqli->insert_id;
        $mysqli->close();
    }

    public static function remove_cart_attribute_for_pears(): void {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        if (self::$cart_products_attributes_id !== null) {
            $attributes_id = self::$cart_products_attributes_id;
            self::exec($mysqli, "DELETE FROM products_attributes WHERE products_attributes_id = $attributes_id");
        } else {
            self::exec(
                $mysqli,
                'DELETE FROM products_attributes WHERE products_id = 3 AND options_id = ' . self::CART_OPTION_ID
            );
        }

        self::exec(
            $mysqli,
            'DELETE FROM products_options_values_to_products_options WHERE products_options_id = '
            . self::CART_OPTION_ID
        );
        self::exec(
            $mysqli,
            'DELETE FROM products_options_values WHERE products_options_values_id = ' . self::CART_VALUE_ID
        );
        self::exec(
            $mysqli,
            'DELETE FROM products_options WHERE products_options_id = ' . self::CART_OPTION_ID
        );

        $mysqli->close();
        self::$cart_products_attributes_id = null;
    }

    public static function cart_option_id(): int {
        return self::CART_OPTION_ID;
    }

    public static function cart_value_id(): int {
        return self::CART_VALUE_ID;
    }

    public static function insert_priced_cart_attribute_for_pears(): void {
        self::remove_priced_cart_attribute_for_pears();

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        self::exec(
            $mysqli,
            'INSERT INTO products_options (products_options_id, language_id, products_options_name, sort_order)'
            . " VALUES (" . self::PRICED_OPTION_ID . ", 1, 'HTTP Test Priced Option', 97)"
        );
        self::exec(
            $mysqli,
            'INSERT INTO products_options_values (products_options_values_id, language_id, products_options_values_name, sort_order)'
            . " VALUES (" . self::PRICED_VALUE_ID . ", 1, 'HTTP Priced Add-on', 97)"
        );
        self::exec(
            $mysqli,
            'INSERT INTO products_options_values_to_products_options (products_options_id, products_options_values_id)'
            . ' VALUES (' . self::PRICED_OPTION_ID . ', ' . self::PRICED_VALUE_ID . ')'
        );
        self::exec(
            $mysqli,
            'INSERT INTO products_attributes (products_id, options_id, options_values_id, options_values_price, price_prefix)'
            . ' VALUES (3, ' . self::PRICED_OPTION_ID . ', ' . self::PRICED_VALUE_ID . ", '1.2500', '+')"
        );

        self::$priced_products_attributes_id = (int) $mysqli->insert_id;
        $mysqli->close();
    }

    public static function remove_priced_cart_attribute_for_pears(): void {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        if (self::$priced_products_attributes_id !== null) {
            $attributes_id = self::$priced_products_attributes_id;
            self::exec($mysqli, "DELETE FROM products_attributes WHERE products_attributes_id = $attributes_id");
        } else {
            self::exec(
                $mysqli,
                'DELETE FROM products_attributes WHERE products_id = 3 AND options_id = ' . self::PRICED_OPTION_ID
            );
        }

        self::exec(
            $mysqli,
            'DELETE FROM products_options_values_to_products_options WHERE products_options_id = '
            . self::PRICED_OPTION_ID
        );
        self::exec(
            $mysqli,
            'DELETE FROM products_options_values WHERE products_options_values_id = ' . self::PRICED_VALUE_ID
        );
        self::exec(
            $mysqli,
            'DELETE FROM products_options WHERE products_options_id = ' . self::PRICED_OPTION_ID
        );

        $mysqli->close();
        self::$priced_products_attributes_id = null;
    }

    public static function priced_option_id(): int {
        return self::PRICED_OPTION_ID;
    }

    public static function priced_value_id(): int {
        return self::PRICED_VALUE_ID;
    }

    public static function install_extra_shipping_modules(): void {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        if (self::$saved_shipping_installed === null) {
            self::$saved_shipping_installed = self::fetch_configuration_value($mysqli, 'MODULE_SHIPPING_INSTALLED') ?? 'flat.php';
        }

        self::set_configuration($mysqli, 'MODULE_SHIPPING_INSTALLED', 'flat.php;item.php;zones.php;table.php');
        self::set_configuration($mysqli, 'MODULE_SHIPPING_ITEM_STATUS', 'True');
        self::set_configuration($mysqli, 'MODULE_SHIPPING_ITEM_COST', '2.50');
        self::set_configuration($mysqli, 'MODULE_SHIPPING_ITEM_HANDLING', '0');
        self::set_configuration($mysqli, 'MODULE_SHIPPING_ZONES_STATUS', 'True');
        self::set_configuration($mysqli, 'MODULE_SHIPPING_ZONES_COUNTRIES_1', 'US');
        self::set_configuration($mysqli, 'MODULE_SHIPPING_ZONES_COST_1', '3:8.50,7:10.50,99:20.00');
        self::set_configuration($mysqli, 'MODULE_SHIPPING_ZONES_HANDLING_1', '0');
        self::set_configuration($mysqli, 'MODULE_SHIPPING_TABLE_STATUS', 'True');
        self::set_configuration($mysqli, 'MODULE_SHIPPING_TABLE_COST', '25:8.50,50:5.50,10000:0.00');
        self::set_configuration($mysqli, 'MODULE_SHIPPING_TABLE_MODE', 'weight');
        self::set_configuration($mysqli, 'MODULE_SHIPPING_TABLE_HANDLING', '0');

        $mysqli->close();
    }

    public static function restore_shipping_modules(): void {
        if (self::$saved_shipping_installed === null) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        self::set_configuration($mysqli, 'MODULE_SHIPPING_INSTALLED', self::$saved_shipping_installed);
        self::set_configuration($mysqli, 'MODULE_SHIPPING_ITEM_STATUS', 'False');
        self::set_configuration($mysqli, 'MODULE_SHIPPING_ZONES_STATUS', 'False');
        self::set_configuration($mysqli, 'MODULE_SHIPPING_TABLE_STATUS', 'False');

        $mysqli->close();
        self::$saved_shipping_installed = null;
    }

    public static function restrict_flat_shipping_to_non_fixture_geo_zone(): void {
        self::restore_flat_shipping_geo_zone();

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        self::$saved_flat_zone = self::fetch_configuration_value($mysqli, 'MODULE_SHIPPING_FLAT_ZONE') ?? '0';
        self::ensure_excluding_geo_zone($mysqli);
        self::set_configuration($mysqli, 'MODULE_SHIPPING_FLAT_ZONE', (string) self::EXCLUDING_GEO_ZONE_ID);

        $mysqli->close();
    }

    public static function restore_flat_shipping_geo_zone(): void {
        if (self::$saved_flat_zone === null) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        self::set_configuration($mysqli, 'MODULE_SHIPPING_FLAT_ZONE', self::$saved_flat_zone);

        $mysqli->close();
        self::$saved_flat_zone = null;
    }

    public static function enable_free_shipping_over_one_dollar(): void {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        if (self::$saved_free_shipping === null) {
            self::$saved_free_shipping = self::fetch_configuration_value($mysqli, 'MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') ?? 'False';
            self::$saved_free_shipping_over = self::fetch_configuration_value($mysqli, 'MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER') ?? '50';
        }

        self::set_configuration($mysqli, 'MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING', 'True');
        self::set_configuration($mysqli, 'MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER', '1');

        $mysqli->close();
    }

    public static function restore_free_shipping(): void {
        if (self::$saved_free_shipping === null) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        self::set_configuration($mysqli, 'MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING', self::$saved_free_shipping);
        if (self::$saved_free_shipping_over !== null) {
            self::set_configuration($mysqli, 'MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER', self::$saved_free_shipping_over);
        }

        $mysqli->close();
        self::$saved_free_shipping = null;
        self::$saved_free_shipping_over = null;
    }

    public static function block_checkout_when_pears_out_of_stock(): void {
        self::restore_pears_stock_and_checkout_flag();

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $result = $mysqli->query('SELECT products_quantity FROM products WHERE products_id = 3 LIMIT 1');
        if ($result !== false) {
            $row = $result->fetch_assoc();
            if (is_array($row)) {
                self::$saved_pears_quantity = (int) $row['products_quantity'];
            }
            $result->free();
        }

        self::$saved_stock_allow_checkout = self::fetch_configuration_value($mysqli, 'STOCK_ALLOW_CHECKOUT') ?? 'true';

        self::exec($mysqli, 'UPDATE products SET products_quantity = 0 WHERE products_id = 3');
        self::set_configuration($mysqli, 'STOCK_ALLOW_CHECKOUT', 'false');

        $mysqli->close();
    }

    public static function restore_pears_stock_and_checkout_flag(): void {
        if (self::$saved_pears_quantity === null && self::$saved_stock_allow_checkout === null) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        if (self::$saved_pears_quantity !== null) {
            $quantity = self::$saved_pears_quantity;
            self::exec($mysqli, "UPDATE products SET products_quantity = $quantity WHERE products_id = 3");
        }

        if (self::$saved_stock_allow_checkout !== null) {
            self::set_configuration($mysqli, 'STOCK_ALLOW_CHECKOUT', self::$saved_stock_allow_checkout);
        }

        $mysqli->close();
        self::$saved_pears_quantity = null;
        self::$saved_stock_allow_checkout = null;
    }

    public static function enable_display_price_with_tax_and_flat_shipping_tax(): void {
        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        if (self::$saved_display_price_with_tax === null) {
            self::$saved_display_price_with_tax = self::fetch_configuration_value($mysqli, 'DISPLAY_PRICE_WITH_TAX') ?? 'false';
            self::$saved_flat_shipping_tax_class = self::fetch_configuration_value($mysqli, 'MODULE_SHIPPING_FLAT_TAX_CLASS') ?? '0';
        }

        self::set_configuration($mysqli, 'DISPLAY_PRICE_WITH_TAX', 'true');
        self::set_configuration($mysqli, 'MODULE_SHIPPING_FLAT_TAX_CLASS', '1');

        $mysqli->close();
    }

    public static function restore_display_price_with_tax_and_flat_shipping_tax(): void {
        if (self::$saved_display_price_with_tax === null && self::$saved_flat_shipping_tax_class === null) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        if (self::$saved_display_price_with_tax !== null) {
            self::set_configuration($mysqli, 'DISPLAY_PRICE_WITH_TAX', self::$saved_display_price_with_tax);
        }

        if (self::$saved_flat_shipping_tax_class !== null) {
            self::set_configuration($mysqli, 'MODULE_SHIPPING_FLAT_TAX_CLASS', self::$saved_flat_shipping_tax_class);
        }

        $mysqli->close();
        self::$saved_display_price_with_tax = null;
        self::$saved_flat_shipping_tax_class = null;
    }

    public static function restrict_cod_to_non_fixture_geo_zone(): void {
        self::restore_cod_geo_zone();

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        self::$saved_cod_zone = self::fetch_configuration_value($mysqli, 'MODULE_PAYMENT_COD_ZONE') ?? '0';

        self::ensure_excluding_geo_zone($mysqli);
        self::set_configuration($mysqli, 'MODULE_PAYMENT_COD_ZONE', (string) self::EXCLUDING_GEO_ZONE_ID);

        $mysqli->close();
    }

    public static function restore_cod_geo_zone(): void {
        if (self::$saved_cod_zone === null) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        self::set_configuration($mysqli, 'MODULE_PAYMENT_COD_ZONE', self::$saved_cod_zone);
        self::exec(
            $mysqli,
            'DELETE FROM zones_to_geo_zones WHERE geo_zone_id = ' . self::EXCLUDING_GEO_ZONE_ID
        );
        self::exec($mysqli, 'DELETE FROM geo_zones WHERE geo_zone_id = ' . self::EXCLUDING_GEO_ZONE_ID);

        $mysqli->close();
        self::$saved_cod_zone = null;
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
        $affected = $statement->affected_rows;
        $statement->close();

        if ($affected > 0) {
            return;
        }

        $existing = self::fetch_configuration_value($mysqli, $configuration_key);
        if ($existing !== null) {
            return;
        }

        $title = $configuration_key;
        $description = 'HTTP checkout fixture';
        $group_id = 6;
        $sort_order = 0;
        $insert = $mysqli->prepare(
            'INSERT INTO configuration (configuration_title, configuration_key, configuration_value,'
            . ' configuration_description, configuration_group_id, sort_order, date_added)'
            . ' VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );

        if ($insert === false) {
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $insert->bind_param('ssssii', $title, $configuration_key, $configuration_value, $description, $group_id, $sort_order);
        $insert->execute();
        $insert->close();
    }

    private static function ensure_excluding_geo_zone(\mysqli $mysqli): void {
        $check = $mysqli->query(
            'SELECT geo_zone_id FROM geo_zones WHERE geo_zone_id = ' . self::EXCLUDING_GEO_ZONE_ID . ' LIMIT 1'
        );
        $exists = $check !== false && $check->num_rows > 0;
        if ($check !== false) {
            $check->free();
        }

        if ($exists) {
            return;
        }

        self::exec(
            $mysqli,
            'INSERT INTO geo_zones (geo_zone_id, geo_zone_name, geo_zone_description, date_added)'
            . " VALUES (" . self::EXCLUDING_GEO_ZONE_ID . ", 'HTTP Test UK Only', 'phoenix-cart-tests zone edge', NOW())"
        );
        self::exec(
            $mysqli,
            'INSERT INTO zones_to_geo_zones (zone_country_id, zone_id, geo_zone_id, date_added)'
            . ' VALUES (222, 0, ' . self::EXCLUDING_GEO_ZONE_ID . ', NOW())'
        );
    }

}
