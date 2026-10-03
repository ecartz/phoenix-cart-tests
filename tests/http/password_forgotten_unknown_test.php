<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_customer_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class password_forgotten_unknown_test extends http_test_case {

    protected function tearDown(): void {
        http_customer_fixture_sql::restore_fixture_password_and_clear_reset_key();
        parent::tearDown();
    }

    public function test_unknown_email_does_not_set_fixture_reset_key(): void {
        $forgot_page = $this->get_http()->request('GET', '/password_forgotten.php');
        $formid = self::parse_hidden_input($forgot_page->getContent(false), 'formid');
        $this->assertNotSame('', $formid);

        $this->get_http()->request('POST', '/password_forgotten.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'email_address' => 'nobody-here@example.com',
            ],
        ]);

        $this->assertNull(http_customer_fixture_sql::password_reset_key_for_fixture_customer());
    }

}
