<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Disposable catalog copy + dedicated DB for web installer acceptance tests.
 */
final class installer_bootstrap
{
    public static function is_enabled(): bool {
        $flag = getenv('PHOENIX_INSTALLER_ENABLED');

        return $flag !== false && $flag !== '' && $flag !== '0';
    }

    public static function base_url(): string {
        return rtrim(self::env('PHOENIX_INSTALLER_BASE_URL', 'http://127.0.0.1:8766'), '/');
    }

    public static function source_catalog_root(): string {
        if (getenv('PHOENIX_CART_ROOT')) {
            return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) getenv('PHOENIX_CART_ROOT')), DIRECTORY_SEPARATOR);
        }

        $default = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'PhoenixCart';
        if (is_dir($default)) {
            return $default;
        }

        throw new \RuntimeException('PHOENIX_CART_ROOT or ./PhoenixCart required for installer tests.');
    }

    public static function catalog_copy_root(): string {
        $relative = self::env('PHOENIX_INSTALLER_CATALOG_ROOT', 'working/installer-catalog');

        if (str_contains($relative, ':') || str_starts_with($relative, DIRECTORY_SEPARATOR) || str_starts_with($relative, '\\\\')) {
            return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative), DIRECTORY_SEPARATOR);
        }

        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    public static function installer_db_name(): string {
        return self::env('PHOENIX_INSTALLER_DB_NAME', 'phoenix_install');
    }

    public static function db_host(): string {
        return self::env('PHOENIX_DB_HOST', '127.0.0.1');
    }

    public static function db_port(): string {
        return self::env('PHOENIX_DB_PORT', '3306');
    }

    public static function db_user(): string {
        return self::env('PHOENIX_DB_USER', 'phoenix');
    }

    public static function db_password(): string {
        return self::env('PHOENIX_DB_PASSWORD', 'phoenix');
    }

    public static function client(int $max_redirects = 10): HttpClientInterface {
        $inner = HttpClient::create([
            'base_uri' => self::base_url(),
            'max_redirects' => 0,
            'headers' => [
                'User-Agent' => 'phoenix-cart-tests-installer/1.0',
            ],
        ]);

        return new cookie_jar_http_client($inner, $max_redirects);
    }

    public static function prepare_catalog(): void {
        $source = self::source_catalog_root();
        $dest = self::catalog_copy_root();

        if (!is_dir($source . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoloader.php')) {
            throw new \RuntimeException('Missing catalog at ' . $source);
        }

        self::cleanup_catalog();

        self::copy_directory($source, $dest);

        $local_configure = $dest . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'configure.php';
        if (is_file($local_configure)) {
            @unlink($local_configure);
        }
    }

    public static function ensure_install_directory(): void {
        $dest_install = self::catalog_copy_root() . DIRECTORY_SEPARATOR . 'install';
        if (is_file($dest_install . DIRECTORY_SEPARATOR . 'index.php')) {
            return;
        }

        $source_install = self::source_catalog_root() . DIRECTORY_SEPARATOR . 'install';
        if (!is_file($source_install . DIRECTORY_SEPARATOR . 'index.php')) {
            throw new \RuntimeException('Missing installer at ' . $source_install);
        }

        self::copy_directory($source_install, $dest_install);
    }

    public static function cleanup_catalog(): void {
        $dest = self::catalog_copy_root();
        if (!is_dir($dest)) {
            return;
        }

        foreach ([
            $dest . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php',
            $dest . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php',
        ] as $configure_path) {
            if (is_file($configure_path)) {
                @chmod($configure_path, 0666);
            }
        }

        self::remove_directory($dest);
    }

    public static function reset_installer_database(): void {
        $name = self::installer_db_name();
        $port = (int) self::db_port();
        $escaped = str_replace('`', '``', $name);

        $root_password = getenv('PHOENIX_MYSQL_ROOT_PASSWORD');
        $credentials = [];
        if ($root_password !== false && $root_password !== '') {
            $credentials[] = [self::db_host(), 'root', (string) $root_password, 'percent'];
        }
        $credentials[] = [
            'localhost',
            'root',
            (string) ($root_password !== false && $root_password !== '' ? $root_password : ''),
            'localhost',
        ];
        $credentials[] = [self::db_host(), self::db_user(), self::db_password(), 'none'];

        $last_error = 'MySQL connection failed';
        foreach ($credentials as [$host, $user, $password, $grant_type]) {
            try {
                $mysqli = new \mysqli($host, $user, $password, '', $port);
            } catch (\mysqli_sql_exception $exception) {
                $last_error = $exception->getMessage();
                continue;
            }

            if ($mysqli->connect_errno) {
                $last_error = $mysqli->connect_error;
                continue;
            }

            if (!$mysqli->query("DROP DATABASE IF EXISTS `{$escaped}`")) {
                $last_error = 'DROP DATABASE failed: ' . $mysqli->error;
                $mysqli->close();
                continue;
            }

            if (!$mysqli->query("CREATE DATABASE `{$escaped}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                $last_error = 'CREATE DATABASE failed: ' . $mysqli->error;
                $mysqli->close();
                continue;
            }

            if ($grant_type === 'percent') {
                if (!$mysqli->query("GRANT ALL PRIVILEGES ON `{$escaped}`.* TO 'phoenix'@'%'")) {
                    $last_error = 'GRANT failed: ' . $mysqli->error;
                    $mysqli->close();
                    continue;
                }
                $mysqli->query('FLUSH PRIVILEGES');
            } elseif ($grant_type === 'localhost' && $user === 'root') {
                if (!$mysqli->query("GRANT ALL PRIVILEGES ON `{$escaped}`.* TO 'phoenix'@'localhost'")) {
                    $last_error = 'GRANT failed: ' . $mysqli->error;
                    $mysqli->close();
                    continue;
                }
                $mysqli->query('FLUSH PRIVILEGES');
            }

            $mysqli->close();

            return;
        }

        self::reset_installer_database_via_shell($last_error);
    }

    private static function reset_installer_database_via_shell(string $prior_error): void {
        $script = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'reset-installer-database.sh';
        if (!is_file($script)) {
            throw new \RuntimeException($prior_error);
        }

        $command = 'bash ' . escapeshellarg($script);
        exec($command, $output, $exit_code);
        if ($exit_code !== 0) {
            $detail = $output !== [] ? implode("\n", $output) : $prior_error;
            throw new \RuntimeException('reset-installer-database.sh failed: ' . $detail);
        }
    }

    public static function catalog_filesystem_root(): string {
        $root = self::catalog_copy_root();

        return rtrim(str_replace('\\', '/', realpath($root) ?: $root), '/') . '/';
    }

    private static function copy_directory(string $source, string $dest): void {
        if (!is_dir($dest) && !mkdir($dest, 0775, true) && !is_dir($dest)) {
            throw new \RuntimeException('Cannot create ' . $dest);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            $target = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
                    throw new \RuntimeException('Cannot create ' . $target);
                }
            } elseif (!copy($item->getPathname(), $target)) {
                throw new \RuntimeException('Cannot copy ' . $item->getPathname());
            }
        }
    }

    private static function remove_directory(string $path): void {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @chmod($item->getPathname(), 0666);
                @unlink($item->getPathname());
            }
        }

        @rmdir($path);
    }

    private static function env(string $name, string $default): string {
        $value = getenv($name);

        return ($value !== false && $value !== '') ? $value : $default;
    }
}
