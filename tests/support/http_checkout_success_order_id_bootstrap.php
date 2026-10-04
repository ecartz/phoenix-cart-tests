<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Copy harness checkout_success order_id shim into the catalog for redirect HTTP tests.
 */
final class http_checkout_success_order_id_bootstrap {

    public const HOOK_FILENAME = 'http_checkout_success_order_id_hook.php';

    public static function install_fixture_hook(): void {
        $repo_root = dirname(__DIR__, 2);
        $hook_source = $repo_root . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'http'
            . DIRECTORY_SEPARATOR . self::HOOK_FILENAME;

        $hook_dir = http_bootstrap::catalog_root() . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
            . 'hooks' . DIRECTORY_SEPARATOR . 'shop' . DIRECTORY_SEPARATOR . 'siteWide';

        if (!is_dir($hook_dir) && !mkdir($hook_dir, 0775, true) && !is_dir($hook_dir)) {
            throw new \RuntimeException('Cannot create catalog hook directory: ' . $hook_dir);
        }

        if (!copy($hook_source, $hook_dir . DIRECTORY_SEPARATOR . self::HOOK_FILENAME)) {
            throw new \RuntimeException('Failed to copy checkout_success order_id hook into catalog.');
        }
    }

    public static function remove_fixture_hook(): void {
        $hook_path = http_bootstrap::catalog_root() . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
            . 'hooks' . DIRECTORY_SEPARATOR . 'shop' . DIRECTORY_SEPARATOR . 'siteWide' . DIRECTORY_SEPARATOR
            . self::HOOK_FILENAME;

        if (is_file($hook_path)) {
            @unlink($hook_path);
        }
    }

}
