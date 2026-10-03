<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class account_password_wrong_test extends http_test_case {

    public function test_wrong_current_password_keeps_account_password_form(): void {
        $this->login_fixture_customer();

        $password_page = $this->get_http()->request('GET', '/account_password.php');
        $password_html = $password_page->getContent(false);
        $password_formid = self::parse_hidden_input($password_html, 'formid');
        $this->assertNotSame('', $password_formid);

        $response = $this->get_http()->request('POST', '/account_password.php', [
            'body' => [
                'action' => 'process',
                'formid' => $password_formid,
                'password_current' => 'definitely-wrong-current-password',
                'password' => 'new-password-should-not-apply',
                'password_confirmation' => 'new-password-should-not-apply',
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getContent(false);
        $this->assertStringContainsString('password_current', $body);
        $this->assertStringNotContainsString('Your password has been successfully updated', $body);
    }

}
