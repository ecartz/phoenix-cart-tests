<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_navbar;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;
use shoppingCart;

#[Group('mysql')]
final class cm_navbar_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->define_constants([
            'MODULE_CONTENT_NAVBAR_FIXED' => 'default',
        ]);
        $this->load_languages_for_installed('MODULE_CONTENT_NAVBAR_INSTALLED', 'modules/navbar');
        $this->load_language_file_if_missing('modules/content/navigation/cm_navbar.php');
        $_SESSION['cart'] = new shoppingCart();
    }

    protected function tearDown(): void {
        unset($_SESSION['cart']);

        parent::tearDown();
    }

    public function test_execute_buffers_module_markup(): void {
        $this->assertTrue(defined('MODULE_CONTENT_NAVBAR_STATUS'));
        $this->assertSame('True', MODULE_CONTENT_NAVBAR_STATUS);

        $this->execute_module(cm_navbar::class);

        $content = $this->buffered_content('navigation');
        $this->assertStringContainsString('cm-navbar', $content);
    }

}
