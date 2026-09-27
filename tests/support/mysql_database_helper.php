<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Real {@see Database} connection and T1 shop bootstrap for Integration tests.
 */
final class mysql_database_helper
{
    private static bool $bootstrapped = false;

    private static ?\Database $connection = null;

    public static function connection(): \Database
    {
        if (self::$connection instanceof \Database) {
            return self::$connection;
        }

        mysql_bootstrap::define_connection_constants();

        $db = new \Database();
        if ($db->connect_error) {
            throw new \RuntimeException('Database connection failed: ' . $db->connect_error);
        }

        self::$connection = $db;
        $GLOBALS['db'] = $db;

        return $db;
    }

    public static function bootstrap_t1(): \Database
    {
        $db = self::connection();

        if (!self::$bootstrapped) {
            require DIR_FS_CATALOG . 'includes/system/segments/application/read_configuration.php';
            mysql_bootstrap::define_catalog_language_constants();
            self::$bootstrapped = true;
        }

        return $db;
    }

    /**
     * Reset static state between PHPUnit processes (testing helper only).
     */
    public static function reset_for_tests(): void
    {
        self::$bootstrapped = false;
        self::$connection = null;
        unset($GLOBALS['db']);
    }
}
