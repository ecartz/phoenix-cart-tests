<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Integration;

use integration_throwaway_module;
use PhoenixCart\Tests\Support\mysql_test_case;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/../Support/integration_throwaway_module.php';

#[Group('mysql')]
final class abstract_module_install_remove_test extends mysql_test_case
{
    private const STATUS_KEY = 'MODULE_PHOENIX_INTEGRATION_PROBE_STATUS';

    protected function tearDown(): void
    {
        $module = new integration_throwaway_module();
        if ($module->check() > 0) {
            $module->remove();
        }

        parent::tearDown();
    }

    public function test_install_writes_configuration_rows(): void
    {
        $module = new integration_throwaway_module();

        $this->assertSame(0, $module->check());
        $module->install();

        $installed = new integration_throwaway_module();
        $this->assertGreaterThan(0, $installed->check());

        $row = $this->db()->query(
            "SELECT configuration_value FROM configuration WHERE configuration_key = '"
            . $this->db()->escape(self::STATUS_KEY) . "'"
        )->fetch_assoc();

        $this->assertSame('False', $row['configuration_value'] ?? null);
    }

    public function test_remove_deletes_configuration_rows(): void
    {
        $module = new integration_throwaway_module();
        $module->install();
        $this->assertGreaterThan(0, (new integration_throwaway_module())->check());

        $module->remove();

        $after = new integration_throwaway_module();
        $this->assertSame(0, $after->check());

        $count = $this->db()->fetch_all(
            "SELECT configuration_key FROM configuration WHERE configuration_key LIKE 'MODULE_PHOENIX_INTEGRATION_PROBE_%'"
        );

        $this->assertSame([], $count);
    }
}
