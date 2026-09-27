<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_footer_account;
use PhoenixCart\Tests\support\content_module_test_case;
use PHPUnit\Framework\Attributes\DataProvider;

final class cm_footer_account_test extends content_module_test_case
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->with_linker();
        $this->define_constants([
            'MODULE_CONTENT_FOOTER_ACCOUNT_STATUS' => 'True',
            'MODULE_CONTENT_FOOTER_ACCOUNT_CONTENT_WIDTH' => 'col-sm-6',
            'MODULE_CONTENT_FOOTER_ACCOUNT_HEADING_TITLE' => 'Customer Services',
            'MODULE_CONTENT_FOOTER_ACCOUNT_BOX_ACCOUNT' => 'Settings',
            'MODULE_CONTENT_FOOTER_ACCOUNT_BOX_ADDRESS_BOOK' => 'Addresses',
            'MODULE_CONTENT_FOOTER_ACCOUNT_BOX_ORDER_HISTORY' => 'Orders',
            'MODULE_CONTENT_FOOTER_ACCOUNT_BOX_LOGOFF' => 'Secure Sign Out',
            'MODULE_CONTENT_FOOTER_ACCOUNT_BOX_CREATE_ACCOUNT' => 'New? Start Here',
            'MODULE_CONTENT_FOOTER_ACCOUNT_BOX_LOGIN' => 'Sign In',
        ]);
    }

    protected function tearDown(): void
    {
        unset($_SESSION['customer_id']);

        parent::tearDown();
    }

    /**
     * @param list<string> $expected_fragments
     * @param list<string> $unexpected_fragments
     */
    #[DataProvider('account_state_provider')]
    public function test_execute_buffers_account_links_for_session_state(
        ?int $customer_id,
        array $expected_fragments,
        array $unexpected_fragments
    ): void {
        if ($customer_id === null) {
            unset($_SESSION['customer_id']);
        } else {
            $_SESSION['customer_id'] = $customer_id;
        }

        $this->reset_template();
        $this->execute_module(cm_footer_account::class);

        $content = $this->buffered_content('footer');
        $this->assertStringContainsString('cm-footer-account', $content);
        foreach ($expected_fragments as $fragment) {
            $this->assertStringContainsString($fragment, $content);
        }
        foreach ($unexpected_fragments as $fragment) {
            $this->assertStringNotContainsString($fragment, $content);
        }
    }

    public static function account_state_provider(): array
    {
        return [
            'guest' => [
                null,
                ['login.php', 'Sign In', 'create_account.php'],
                ['logoff.php', 'Secure Sign Out'],
            ],
            'logged in' => [
                42,
                ['account.php', 'Settings', 'logoff.php', 'Secure Sign Out'],
                ['login.php', 'Sign In'],
            ],
        ];
    }
}
