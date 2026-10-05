<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Detect whether the pinned CE catalog order edit UI exposes line-item controls.
 */
final class catalog_order_editor_probe {

    public static function edit_html_has_line_editor_controls(string $html): bool {
        if (preg_match('/update_products\s*\[/i', $html) === 1) {
            return true;
        }

        if (preg_match('/add_order_product/i', $html) === 1) {
            return true;
        }

        if (preg_match('/remove_order_product/i', $html) === 1) {
            return true;
        }

        if (
            preg_match('/orders_products/i', $html) === 1
            && preg_match('/name=["\'][^"\']*(?:qty|quantity|price)[^"\']*["\']/i', $html) === 1
        ) {
            return true;
        }

        return false;
    }

    /**
     * Cheap check before installer wizard runs (reads the pinned edit view from disk).
     */
    public static function pinned_edit_view_has_line_editor_controls(): bool {
        $html = self::pinned_edit_view_html();

        return $html !== '' && self::edit_html_has_line_editor_controls($html);
    }

    public static function pinned_edit_view_html(): string {
        $root = self::catalog_root();
        if ($root === '') {
            return '';
        }

        $edit_view = $root . '/admin/includes/actions/orders/views/edit.php';
        if (!is_readable($edit_view)) {
            return '';
        }

        $html = file_get_contents($edit_view);

        return $html === false ? '' : $html;
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
