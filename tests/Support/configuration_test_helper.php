<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Support;

/**
 * Load shop constants the same way as production {@see read_configuration.php}.
 */
final class configuration_test_helper {

    /**
     * @param list<array{configuration_key: string, configuration_value: string}> $rows
     */
    public static function load_from_configuration_rows(array $rows, ?mock_catalog_database $database = null): mock_catalog_database
    {
        $database ??= new mock_catalog_database($rows);
        $db = $database;

        require DIR_FS_CATALOG . 'includes/system/segments/application/read_configuration.php';

        return $database;
    }

}
