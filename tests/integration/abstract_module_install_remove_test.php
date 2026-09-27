<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use integration_throwaway_module;
use PhoenixCart\Tests\support\mysql_test_case;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/../Support/integration_throwaway_module.php';

#[Group('mysql')]
final class abstract_module_install_remove_test extends mysql_test_case
{
    private const STATUS_KEY = 'MODULE_PHOENIX_INTEGRATION_PROBE_STATUS';

    protected function setUp(): void
    {
        parent::setUp();
        $this->purge_throwaway_configuration();
    }

    protected function tearDown(): void
    {
        $this->purge_throwaway_configuration();

        parent::tearDown();
    }

    /**
     * Direct SQL cleanup — {@see abstract_module::remove()} calls keys(), which can
     * re-insert missing constants before DELETE and throw duplicate-key errors.
     */
    private function purge_throwaway_configuration(): void
    {
        $prefix = integration_throwaway_module::CONFIG_KEY_BASE;
        $this->db()->query(
            "DELETE FROM configuration WHERE configuration_key LIKE '"
            . $this->db()->escape($prefix) . "%'"
        );
    }

    /** Match install seed values so {@see abstract_module::remove()} does not re-insert via keys(). */
    private function define_throwaway_constants_for_remove(): void
    {
        if (!defined('MODULE_PHOENIX_INTEGRATION_PROBE_STATUS')) {
            define('MODULE_PHOENIX_INTEGRATION_PROBE_STATUS', 'False');
        }
        if (!defined('MODULE_PHOENIX_INTEGRATION_PROBE_SORT_ORDER')) {
            define('MODULE_PHOENIX_INTEGRATION_PROBE_SORT_ORDER', '0');
        }
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

        $this->define_throwaway_constants_for_remove();
        $module->remove();

        $after = new integration_throwaway_module();
        $this->assertSame(0, $after->check());

        $count = $this->db()->fetch_all(
            "SELECT configuration_key FROM configuration WHERE configuration_key LIKE 'MODULE_PHOENIX_INTEGRATION_PROBE_%'"
        );

        $this->assertSame([], $count);
    }
}
