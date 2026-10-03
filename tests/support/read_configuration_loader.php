<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Load configuration constants like {@see read_configuration.php} without redefinition warnings.
 */
final class read_configuration_loader {

    public static function load_from_global_database(): void {
        /** @var object $db */
        $db = $GLOBALS['db'];

        foreach ($db->fetch_all('SELECT configuration_key, configuration_value FROM configuration') as $row) {
            self::define_if_absent((string) $row['configuration_key'], (string) $row['configuration_value']);
        }
    }

    private static function define_if_absent(string $key, string $value): void {
        if (defined($key)) {
            return;
        }

        define($key, $value);
    }

}
