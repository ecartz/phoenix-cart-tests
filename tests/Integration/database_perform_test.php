<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Integration;

use PhoenixCart\Tests\Support\mysql_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class database_perform_test extends mysql_test_case
{
    private const TEST_KEY = 'PHOENIX_INTEGRATION_TEST_KEY';

    protected function tearDown(): void
    {
        $this->db()->query(
            "DELETE FROM configuration WHERE configuration_key = '" . $this->db()->escape(self::TEST_KEY) . "'"
        );

        parent::tearDown();
    }

    public function test_perform_inserts_configuration_row(): void
    {
        $inserted = $this->db()->perform('configuration', [
            'configuration_title' => 'PHPUnit integration probe',
            'configuration_key' => self::TEST_KEY,
            'configuration_value' => 'True',
            'configuration_description' => 'Ephemeral wave 3 test row',
            'configuration_group_id' => '6',
            'sort_order' => '9999',
            'date_added' => 'NOW()',
        ]);

        $this->assertNotFalse($inserted);

        $row = $this->db()->query(
            "SELECT configuration_value FROM configuration WHERE configuration_key = '"
            . $this->db()->escape(self::TEST_KEY) . "'"
        )->fetch_assoc();

        $this->assertSame('True', $row['configuration_value'] ?? null);
    }
}
