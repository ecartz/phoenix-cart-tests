<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_customer_fixture_sql;
use PhoenixCart\Tests\support\http_testimonial_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class testimonial_write_test extends http_test_case {

    private const TESTIMONIAL_NICK = 'HTTP Fixture Nick';

    private const TESTIMONIAL_TEXT = 'Phoenix HTTP testimonial write body.';

    protected function tearDown(): void {
        $customer_id = http_customer_fixture_sql::customer_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        if ($customer_id !== null) {
            http_testimonial_fixture_sql::delete_testimonials_for_customer_id($customer_id);
        }
        http_testimonial_fixture_sql::delete_testimonials_with_nick(self::TESTIMONIAL_NICK);
        parent::tearDown();
    }

    public function test_logged_in_customer_can_submit_testimonial(): void {
        $this->login_fixture_customer();

        $write_page = $this->get_http()->request('GET', '/testimonials.php');
        $this->assertSame(200, $write_page->getStatusCode());
        $write_html = $write_page->getContent(false);
        $this->assertStringContainsString('cm-t-write', $write_html);
        $formid = self::parse_hidden_input($write_html, 'formid');
        $this->assertNotSame('', $formid);

        $response = $this->get_http()->request('POST', '/testimonials.php', [
            'query' => [
                'action' => 'testimonial_write',
            ],
            'body' => [
                'formid' => $formid,
                'nickname' => self::TESTIMONIAL_NICK,
                'text' => self::TESTIMONIAL_TEXT,
            ],
        ]);

        $final_url = (string) ($response->getInfo('url') ?? '');
        $this->assertStringContainsString('testimonials.php', $final_url);
        $this->assertStringContainsString(
            self::TESTIMONIAL_NICK,
            $response->getContent(false)
        );
    }

}
