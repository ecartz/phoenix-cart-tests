<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

abstract class mysql_test_case extends phoenix_test_case
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!mysql_bootstrap::is_enabled()) {
            self::markTestSkipped(
                'Integration tests skipped. Set PHOENIX_MYSQL_ENABLED=1 and import fixtures/phoenix.sql.'
            );
        }

        if (!extension_loaded('mysqli')) {
            self::markTestSkipped('Integration tests require the mysqli extension.');
        }

        try {
            mysql_database_helper::bootstrap_t1();
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                'Database unavailable with PHOENIX_MYSQL_ENABLED=1: ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    protected function db(): \Database
    {
        return mysql_database_helper::connection();
    }
}
