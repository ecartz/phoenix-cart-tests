<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use PhoenixCart\Tests\support\phoenix_test_case;
use ReflectionClass;
use Versions;

final class versions_test extends phoenix_test_case {

    protected function tearDown(): void {
        $this->reset_versions_state();

        parent::tearDown();
    }

    public function test_set_and_get_returns_stored_version(): void {
        Versions::set('Custom', '2.0.0');

        $this->assertSame('2.0.0', Versions::get('Custom'));
        $this->assertTrue(Versions::has('Custom'));
    }

    public function test_register_loader_loads_on_demand(): void {
        Versions::register_loader('Lazy', static fn (): string => '3.1.4');

        $this->assertSame('3.1.4', Versions::get('Lazy'));
    }

    public function test_get_returns_null_for_unknown_name_without_loader(): void {
        $this->assertNull(Versions::get('MissingProduct'));
    }

    public function test_load_uses_explicit_loader_callable(): void {
        Versions::load('Plugin', static fn (): string => '9.9.9');

        $this->assertSame('9.9.9', Versions::get('Plugin'));
        $this->assertTrue(Versions::has('Plugin'));
    }

    public function test_get_phoenix_reads_catalog_version_file(): void {
        $version_file = DIR_FS_CATALOG . 'includes/version.php';
        $this->assertFileExists($version_file);

        $expected = trim((string) file_get_contents($version_file));
        $this->assertNotSame('', $expected);
        $this->assertSame($expected, Versions::get('Phoenix'));
        $this->assertTrue(Versions::has('Phoenix'));
    }

    private function reset_versions_state(): void {
        $reflection = new ReflectionClass(Versions::class);

        foreach (['versions', 'loaders'] as $property_name) {
            $property = $reflection->getProperty($property_name);
            $property->setAccessible(true);
            $property->setValue(null, $property->getDefaultValue());
        }
    }

}
