<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class login_form_test extends http_test_case
{
    public function test_login_page_renders_login_form_module(): void {
        $response = $this->get_http()->request('GET', '/login.php');

        $this->assertSame(200, $response->getStatusCode());

        $body = $response->getContent(false);
        $this->assertStringContainsString('cm-login-form', $body);
        $this->assertStringContainsString('name="password"', $body);
        $this->assertStringContainsString('email_address', $body);
    }
}
