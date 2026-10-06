<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_create_account_link;
use PhoenixCart\Tests\support\content_module_test_case;

final class cm_create_account_link_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        if (is_file(DIR_FS_CATALOG . 'includes/languages/english/modules/content/login/cm_create_account_link.php')) {
            require DIR_FS_CATALOG . 'includes/languages/english/modules/content/login/cm_create_account_link.php';
        }

        $this->define_constants([
            'MODULE_CONTENT_CREATE_ACCOUNT_LINK_STATUS' => 'True',
            'MODULE_CONTENT_CREATE_ACCOUNT_LINK_CONTENT_WIDTH' => 'col-sm-12',
        ]);

        $this->with_linker();
    }

    public function test_execute_buffers_module_markup(): void {
        $this->execute_module(cm_create_account_link::class);

        $content = $this->buffered_content('login');
        $this->assertStringContainsString('cm-create-account-link', $content);
    }

}
