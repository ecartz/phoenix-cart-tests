<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\integration;

use cm_cs_downloads;
use PhoenixCart\Tests\support\mysql_content_module_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class cm_cs_downloads_test extends mysql_content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->prepare_storefront();
        $this->load_language(
            'modules/content/checkout_success/cm_cs_downloads.php',
            'HEADING_DOWNLOAD'
        );
        $this->define_constants([
            'HEADER_TITLE_MY_ACCOUNT' => 'Account',
        ]);
        $this->seed_customer();
        $this->insert_order('Pears', true);
        $_SERVER['SCRIPT_NAME'] = '/checkout_success.php';
    }

    protected function tearDown(): void {
        $this->delete_customer();

        parent::tearDown();
    }

    public function test_execute_lists_downloadable_order_product(): void {
        $this->assertSame('true', DOWNLOAD_ENABLED);

        $this->execute_module(cm_cs_downloads::class);

        $content = $this->buffered_content('checkout_success');
        $this->assertStringContainsString('cm-cs-downloads', $content);
        $this->assertStringContainsString('Pears', $content);
    }

}
