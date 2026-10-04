<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\catalog_order_editor_probe;
use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;

#[Group('installer')]
final class admin_order_line_editor_test extends install_test_case {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        if (!catalog_order_editor_probe::has_line_editor_post_endpoint()) {
            self::markTestSkipped(
                'Pinned catalog has no admin order line-editor POST endpoint (see fixtures/catalog_pin.txt).'
            );
        }

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_order_line_editor_endpoints_are_present(): void {
        $this->assertTrue(catalog_order_editor_probe::has_line_editor_post_endpoint());
    }

}
