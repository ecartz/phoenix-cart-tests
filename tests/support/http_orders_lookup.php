<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Read storefront order rows for HTTP acceptance assertions (mysqli, same env as http fixtures).
 */
final class http_orders_lookup {

    public static function latest_payment_method_for_email(string $customers_email_address): ?string {
        mysql_bootstrap::define_connection_constants();

        $mysqli = self::connect();

        $statement = $mysqli->prepare(
            'SELECT payment_method FROM orders WHERE customers_email_address = ? ORDER BY orders_id DESC LIMIT 1'
        );

        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('s', $customers_email_address);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result !== false ? $result->fetch_assoc() : false;
        $statement->close();
        $mysqli->close();

        if (!is_array($row)) {
            return null;
        }

        return (string) $row['payment_method'];
    }

    public static function max_orders_id_for_email(string $customers_email_address): int {
        mysql_bootstrap::define_connection_constants();

        $mysqli = self::connect();

        $statement = $mysqli->prepare(
            'SELECT COALESCE(MAX(orders_id), 0) AS max_id FROM orders WHERE customers_email_address = ?'
        );

        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('s', $customers_email_address);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result !== false ? $result->fetch_assoc() : false;
        $statement->close();
        $mysqli->close();

        if (!is_array($row)) {
            return 0;
        }

        return (int) $row['max_id'];
    }

    public static function latest_orders_id_for_email(string $customers_email_address): int {
        return self::max_orders_id_for_email($customers_email_address);
    }

    public static function orders_status_history_comment_for_order(int $orders_id): ?string {
        if ($orders_id <= 0) {
            return null;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $statement = $mysqli->prepare(
            'SELECT comments FROM orders_status_history WHERE orders_id = ? ORDER BY orders_status_history_id DESC LIMIT 1'
        );

        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('i', $orders_id);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result !== false ? $result->fetch_assoc() : false;
        $statement->close();
        $mysqli->close();

        if (!is_array($row)) {
            return null;
        }

        $comments = (string) $row['comments'];

        return $comments === '' ? null : $comments;
    }

    /**
     * @return array{title: string, text: string, value: float}|null
     */
    public static function ot_row_for_order(int $orders_id, string $class): ?array {
        if ($orders_id <= 0) {
            return null;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $statement = $mysqli->prepare(
            'SELECT title, text, value FROM orders_total WHERE orders_id = ? AND class = ? ORDER BY sort_order ASC LIMIT 1'
        );

        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('is', $orders_id, $class);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result !== false ? $result->fetch_assoc() : false;
        $statement->close();
        $mysqli->close();

        if (!is_array($row)) {
            return null;
        }

        return [
            'title' => (string) $row['title'],
            'text' => (string) $row['text'],
            'value' => (float) $row['value'],
        ];
    }

    /**
     * @return array{title: string, text: string, value: float}|null
     */
    public static function ot_tax_row_for_order(int $orders_id): ?array {
        return self::ot_row_for_order($orders_id, 'ot_tax');
    }

    /**
     * @return array{products_name: string, products_quantity: int, products_price: float, final_price: float}|null
     */
    public static function orders_products_line_for_order(int $orders_id, int $products_id): ?array {
        if ($orders_id <= 0) {
            return null;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $statement = $mysqli->prepare(
            'SELECT products_name, products_quantity, products_price, final_price'
            . ' FROM orders_products WHERE orders_id = ? AND products_id = ? ORDER BY orders_products_id ASC LIMIT 1'
        );

        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('ii', $orders_id, $products_id);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result !== false ? $result->fetch_assoc() : false;
        $statement->close();
        $mysqli->close();

        if (!is_array($row)) {
            return null;
        }

        return [
            'products_name' => (string) $row['products_name'],
            'products_quantity' => (int) $row['products_quantity'],
            'products_price' => (float) $row['products_price'],
            'final_price' => (float) $row['final_price'],
        ];
    }

    /**
     * @return list<array{products_options: string, products_options_values: string, options_values_price: float, price_prefix: string}>
     */
    public static function orders_products_attribute_rows_for_order(int $orders_id): array {
        if ($orders_id <= 0) {
            return [];
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $statement = $mysqli->prepare(
            'SELECT products_options, products_options_values, options_values_price, price_prefix'
            . ' FROM orders_products_attributes WHERE orders_id = ? ORDER BY orders_products_attributes_id ASC'
        );

        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('i', $orders_id);
        $statement->execute();
        $result = $statement->get_result();

        $rows = [];
        if ($result !== false) {
            while ($row = $result->fetch_assoc()) {
                if (!is_array($row)) {
                    continue;
                }

                $rows[] = [
                    'products_options' => (string) $row['products_options'],
                    'products_options_values' => (string) $row['products_options_values'],
                    'options_values_price' => (float) $row['options_values_price'],
                    'price_prefix' => (string) $row['price_prefix'],
                ];
            }
        }

        $statement->close();
        $mysqli->close();

        return $rows;
    }

    public static function orders_products_download_id_for_order(int $orders_id): ?int {
        if ($orders_id <= 0) {
            return null;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $statement = $mysqli->prepare(
            'SELECT orders_products_download_id FROM orders_products_download WHERE orders_id = ? ORDER BY orders_products_download_id ASC LIMIT 1'
        );

        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('i', $orders_id);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result !== false ? $result->fetch_assoc() : false;
        $statement->close();
        $mysqli->close();

        if (!is_array($row)) {
            return null;
        }

        return (int) $row['orders_products_download_id'];
    }

    public static function set_order_status(int $orders_id, int $orders_status_id): void {
        if ($orders_id <= 0) {
            return;
        }

        mysql_bootstrap::define_connection_constants();
        $mysqli = self::connect();

        $statement = $mysqli->prepare('UPDATE orders SET orders_status = ? WHERE orders_id = ?');
        if ($statement === false) {
            $mysqli->close();
            throw new \RuntimeException('Prepare failed: ' . $mysqli->error);
        }

        $statement->bind_param('ii', $orders_status_id, $orders_id);
        $statement->execute();
        $statement->close();
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

}
