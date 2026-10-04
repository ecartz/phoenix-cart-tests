<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Detect whether the pinned CE catalog exposes admin order line-editor POST actions.
 */
final class catalog_order_editor_probe {

    /** @var list<string> */
    private const ACTION_FILES = [
        'update_products.php',
        'add_order_product.php',
        'remove_order_product.php',
        'insert_product.php',
    ];

    public static function has_line_editor_post_endpoint(): bool {
        $root = self::catalog_root();
        if ($root === '') {
            return false;
        }

        $actions_dir = $root . '/admin/includes/actions/orders';
        if (!is_dir($actions_dir)) {
            return false;
        }

        foreach (self::ACTION_FILES as $file) {
            if (is_file($actions_dir . '/' . $file)) {
                return true;
            }
        }

        $edit_view = $actions_dir . '/views/edit.php';
        if (!is_readable($edit_view)) {
            return false;
        }

        $html = file_get_contents($edit_view);
        if ($html === false) {
            return false;
        }

        return preg_match(
            "/set_parameter\\('action',\\s*'(update_products|add_order_product|remove_order_product)'\\)/",
            $html
        ) === 1;
    }

    private static function catalog_root(): string {
        $root = getenv('PHOENIX_CART_ROOT');
        if (is_string($root) && $root !== '' && is_dir($root)) {
            return rtrim($root, '/');
        }

        $default = dirname(__DIR__, 2) . '/PhoenixCart';

        return is_dir($default) ? $default : '';
    }

}
