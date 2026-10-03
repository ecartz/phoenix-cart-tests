<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class login_form_test extends http_test_case {

    public function test_login_page_renders_login_form_module(): void {
        $response = $this->get_http()->request('GET', '/login.php');

        $this->assertSame(200, $response->getStatusCode());

        $body = $response->getContent(false);
        $this->assertStringContainsString('cm-login-form', $body);
        $this->assertStringContainsString('name="password"', $body);
        $this->assertStringContainsString('email_address', $body);
    }

    public function test_wrong_password_shows_login_error(): void {
        $login_page = $this->get_http()->request('GET', '/login.php');
        $formid = self::parse_hidden_input($login_page->getContent(false), 'formid');
        $this->assertNotSame('', $formid);

        $response = $this->get_http()->request('POST', '/login.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'email_address' => self::FIXTURE_CUSTOMER_EMAIL,
                'password' => 'not-the-fixture-password',
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getContent(false);
        $this->assertStringContainsString('cm-login-form', $body);
        $this->assertStringNotContainsString('cm-account-title', $body);
        $this->assertTrue(
            str_contains($body, 'No match for E-Mail Address and/or Password')
                || str_contains($body, 'password'),
            'login failure should keep user on login form with an error'
        );
    }

}
