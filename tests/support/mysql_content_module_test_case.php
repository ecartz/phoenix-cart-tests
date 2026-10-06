<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

use default_template;
use hooks;
use Linker;
use ReflectionClass;
use Template;

/**
 * Integration tests for content modules that call {@see execute()} with real {@see Database}.
 */
abstract class mysql_content_module_test_case extends mysql_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->define_constants([
            'MODULE_CONTENT_BOOTSTRAP_ROW_DESCRIPTION' => 'Bootstrap row note',
            'BOOTSTRAP_CONTENT' => 8,
        ]);

        $GLOBALS['Template'] = new Template(new default_template());
        $GLOBALS['all_hooks'] ??= $GLOBALS['hooks'] ?? new hooks('shop');
    }

    /**
     * @param array<string, mixed> $constants
     */
    protected function define_constants(array $constants): void {
        foreach ($constants as $name => $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }
    }

    protected function with_linker(string $prefix = 'https://shop.example.com/'): void {
        $this->define_constants([
            'HTTP_SERVER' => 'https://shop.example.com',
            'DIR_WS_CATALOG' => '/',
            'SESSION_FORCE_COOKIE_USE' => 'False',
        ]);

        $_SERVER['SCRIPT_NAME'] ??= '/index.php';
        $GLOBALS['Linker'] = new Linker($prefix);
    }

    protected function execute_module(string $class): void {
        $previous_directory = getcwd();
        $buffer_level = ob_get_level();
        chdir(DIR_FS_CATALOG);

        try {
            $module = new $class();
            $module->execute();
        } catch (\Throwable $exception) {
            while (ob_get_level() > $buffer_level) {
                ob_end_clean();
            }

            throw $exception;
        } finally {
            chdir($previous_directory);
        }
    }

    protected function buffered_content(string $group): string {
        /** @var Template $template */
        $template = $GLOBALS['Template'];

        if (!$template->has_content($group)) {
            return '';
        }

        $reflection = new ReflectionClass($template);
        $property = $reflection->getProperty('_content');
        $property->setAccessible(true);
        /** @var array<string, list<string>> $stored */
        $stored = $property->getValue($template);

        return implode('', $stored[$group] ?? []);
    }

    protected function reset_template(): void {
        $GLOBALS['Template'] = new Template(new default_template());
    }

    public const FIXTURE_CUSTOMER_ID = 9001;

    protected function load_language(string $relative, string $sentinel): void {
        if (defined($sentinel)) {
            return;
        }

        require_once DIR_FS_CATALOG . 'includes/languages/english/' . ltrim($relative, '/');
    }

    protected function prepare_storefront(): void {
        $_SESSION['languages_id'] = 1;
        $_SESSION['currency'] = defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'USD';
        $this->with_linker();

        $GLOBALS['hooks'] = $GLOBALS['all_hooks'] ?? new hooks('shop');
        $GLOBALS['all_hooks'] = $GLOBALS['hooks'];
        $GLOBALS['messageStack'] = new \messageStack();
        $GLOBALS['currencies'] = new \currencies();

        if (!isset($GLOBALS['short_date_formatter'])) {
            $GLOBALS['short_date_formatter'] = new \IntlDateFormatter(
                'en',
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::NONE
            );
        }

        $this->load_language(
            'system/versioned/1.0.9.3/split_page_results.php',
            'TEXT_DISPLAY_NUMBER_OF_PRODUCTS'
        );
        $this->load_language(
            'system/versioned/1.0.7.other/1.0.7.12/product.php',
            'IS_PRODUCT_SHOW_PRICE'
        );
        $this->define_constants([
            'TEXT_NO_PRODUCTS' => 'There are no products available in this category.',
            'TEXT_SORT_BY' => 'Sort by',
            'STAR_RATING' => 'Rated %s Stars',
            'IMAGE_BUTTON_CLOSE' => 'Close',
            'MATC_BUTTON_CLOSE' => 'Close',
            'PRODUCT_REMOVED' => '%s has been removed from your Cart',
            'STOCK_MARK_PRODUCT_OUT_OF_STOCK' => '***',
        ]);
    }

    protected function prepare_category_tree(): void {
        $this->prepare_storefront();
        $GLOBALS['category_tree'] = new \category_tree();
    }

    protected function seed_customer(bool $extra_address = false): void {
        $this->delete_customer();

        $id = self::FIXTURE_CUSTOMER_ID;
        $db = $this->db();
        $db->query(
            "INSERT INTO customers (
                customers_id, customers_gender, customers_firstname, customers_lastname,
                customers_dob, customers_email_address, customers_default_address_id,
                customers_telephone, customers_fax, customers_password, customers_newsletter, status
            ) VALUES (
                {$id}, 'm', 'Fixture', 'Customer', NULL, 'content-module-fixture@example.com',
                {$id}, '555-0100', NULL, 'hashed-password', '0', 1
            )"
        );
        $db->query(
            "INSERT INTO address_book (
                address_book_id, customers_id, entry_gender, entry_company, entry_firstname,
                entry_lastname, entry_street_address, entry_suburb, entry_postcode, entry_city,
                entry_state, entry_country_id, entry_zone_id
            ) VALUES (
                {$id}, {$id}, 'm', '', 'Fixture', 'Customer', '1 Test Street', '',
                '90210', 'Testville', 'Florida', 223, 18
            )"
        );
        if ($extra_address) {
            $extra = $id + 1;
            $db->query(
                "INSERT INTO address_book (
                    address_book_id, customers_id, entry_gender, entry_company, entry_firstname,
                    entry_lastname, entry_street_address, entry_suburb, entry_postcode, entry_city,
                    entry_state, entry_country_id, entry_zone_id
                ) VALUES (
                    {$extra}, {$id}, 'm', '', 'Fixture', 'Customer', '9 Other Street', '',
                    '10001', 'Othercity', 'Florida', 223, 18
                )"
            );
        }
        $db->query(
            "INSERT INTO customers_info (
                customers_info_id, customers_info_date_of_last_logon, customers_info_number_of_logons,
                customers_info_date_account_created, customers_info_date_account_last_modified,
                global_product_notifications, password_reset_key, password_reset_date
            ) VALUES (
                {$id}, NULL, 0, NOW(), NOW(), 0, NULL, NULL
            )"
        );

        $_SESSION['customer_id'] = $id;
        $_SESSION['languages_id'] = 1;
        if (!isset($GLOBALS['customer_data'])) {
            $GLOBALS['customer_data'] = new \customer_data();
        }
        $GLOBALS['customer'] = new \customer((string) $id);
        $GLOBALS['port_my_data'] = [];
    }

    protected function delete_customer(): void {
        $id = self::FIXTURE_CUSTOMER_ID;
        $db = $this->db();
        $order_ids = [];
        $orders = $db->query('SELECT orders_id FROM orders WHERE customers_id = ' . $id);
        while ($row = $orders->fetch_assoc()) {
            $order_ids[] = (int) $row['orders_id'];
        }
        if ($order_ids !== []) {
            $list = implode(',', $order_ids);
            $db->query('DELETE FROM orders_products_download WHERE orders_id IN (' . $list . ')');
            $db->query('DELETE FROM orders_products WHERE orders_id IN (' . $list . ')');
            $db->query('DELETE FROM orders_total WHERE orders_id IN (' . $list . ')');
            $db->query('DELETE FROM orders WHERE orders_id IN (' . $list . ')');
        }
        $db->query('DELETE FROM products_notifications WHERE customers_id = ' . $id);
        $db->query('DELETE FROM customers_gdpr WHERE customers_id = ' . $id);
        $db->query('DELETE FROM action_recorder WHERE user_id = ' . $id);
        $db->query('DELETE FROM customers_basket_attributes WHERE customers_id = ' . $id);
        $db->query('DELETE FROM customers_basket WHERE customers_id = ' . $id);
        $db->query('DELETE FROM address_book WHERE customers_id = ' . $id);
        $db->query('DELETE FROM customers_info WHERE customers_info_id = ' . $id);
        $db->query('DELETE FROM customers WHERE customers_id = ' . $id);
        unset($_SESSION['customer_id'], $GLOBALS['customer'], $GLOBALS['order_id'], $GLOBALS['port_my_data']);
    }

    protected function insert_order(string $product_name, bool $with_download = false): int {
        $id = self::FIXTURE_CUSTOMER_ID;
        $db = $this->db();
        $status = $with_download ? 2 : 1;
        $db->query(
            "INSERT INTO orders (
                customers_id, customers_name, customers_street_address, customers_city,
                customers_postcode, customers_country, customers_country_id, customers_telephone,
                customers_email_address, customers_address_format_id, delivery_name,
                delivery_street_address, delivery_city, delivery_postcode, delivery_country,
                delivery_country_id, delivery_address_format_id, billing_name,
                billing_street_address, billing_city, billing_postcode, billing_country,
                billing_country_id, billing_address_format_id, payment_method, date_purchased,
                orders_status, currency, currency_value
            ) VALUES (
                {$id}, 'Fixture Customer', '1 Test Street', 'Testville', '90210', 'United States',
                223, '555-0100', 'content-module-fixture@example.com', 1, 'Fixture Customer',
                '1 Test Street', 'Testville', '90210', 'United States', 223, 1, 'Fixture Customer',
                '1 Test Street', 'Testville', '90210', 'United States', 223, 1, 'Cash on Delivery',
                NOW(), {$status}, 'USD', 1
            )"
        );
        $orders_id = (int) $db->insert_id;
        $safe_name = $db->escape($product_name);
        $db->query(
            "INSERT INTO orders_products (
                orders_id, products_id, products_model, products_name, products_price,
                final_price, products_tax, products_quantity
            ) VALUES (
                {$orders_id}, 3, 'PEA-1', '{$safe_name}', 4.99, 4.99, 0, 1
            )"
        );
        $orders_products_id = (int) $db->insert_id;
        $db->query(
            "INSERT INTO orders_total (orders_id, title, text, value, class, sort_order)
            VALUES ({$orders_id}, 'Total:', '\$12.00', 12, 'ot_total', 4)"
        );
        if ($with_download) {
            $db->query(
                "INSERT INTO orders_products_download (
                    orders_id, orders_products_id, orders_products_filename, download_maxdays, download_count
                ) VALUES ({$orders_id}, {$orders_products_id}, 'apple-pie.zip', 7, 1)"
            );
        }

        $GLOBALS['order_id'] = $orders_id;

        return $orders_id;
    }

    protected function cart_with_pears(): void {
        unset($_SESSION['customer_id']);
        $GLOBALS['messageStack'] = $GLOBALS['messageStack'] ?? new \messageStack();
        $_SESSION['cart'] = new \shoppingCart();
        $_SESSION['cart']->add_cart(3, 1, null, false);
    }

}
