<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class contact_us_test extends http_test_case {

    public function test_contact_form_submission_shows_success_message(): void {
        $this->get_http()->request('GET', '/contact_us.php');
        $page = $this->get_http()->request('GET', '/contact_us.php');
        $html = $page->getContent(false);
        $formid = self::parse_hidden_input($html, 'formid');
        $this->assertNotSame('', $formid);

        $response = $this->get_http()->request('POST', '/contact_us.php', [
            'body' => [
                'action' => 'send',
                'formid' => $formid,
                'name' => 'HTTP Test Visitor',
                'email' => 'visitor@example.com',
                'enquiry' => 'Fixture enquiry from phoenix-cart-tests HTTP suite.',
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getContent(false);
        $this->assertStringContainsString('Your message has been sent to the Shopowner.', $body);
    }

}
