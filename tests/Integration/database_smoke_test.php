<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Integration;

use PhoenixCart\Tests\Support\mysql_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class database_smoke_test extends mysql_test_case
{
    public function test_connection_selects_one(): void
    {
        $row = $this->db()->query('SELECT 1 AS ok')->fetch_assoc();

        $this->assertSame('1', $row['ok'] ?? null);
    }

    public function test_configuration_table_seeded(): void
    {
        $rows = $this->db()->fetch_all('SELECT configuration_key FROM configuration');

        $this->assertGreaterThanOrEqual(500, count($rows));
        $this->assertContains('STORE_COUNTRY', array_column($rows, 'configuration_key'));
    }

    public function test_sample_products_seeded(): void
    {
        $count = $this->db()->fetch_all('SELECT products_id FROM products');

        $this->assertGreaterThanOrEqual(9, count($count));
    }
}
