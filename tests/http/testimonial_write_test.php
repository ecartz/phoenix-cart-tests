<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_testimonial_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class testimonial_write_test extends http_test_case {

    private const TESTIMONIAL_TEXT = 'Phoenix HTTP fixture testimonial body';

    protected function tearDown(): void {
        http_testimonial_fixture_sql::delete_fixture_customer_testimonials();
        parent::tearDown();
    }

    public function test_logged_in_customer_can_submit_storefront_testimonial(): void {
        http_testimonial_fixture_sql::delete_fixture_customer_testimonials();
        $this->login_fixture_customer();

        $page = $this->get_http()->request('GET', '/testimonials.php');
        $this->assertSame(200, $page->getStatusCode());
        $html = $page->getContent(false);
        $this->assertStringContainsString('cm-t-write', $html);

        $formid = self::parse_hidden_input($html, 'formid');
        $this->assertNotSame('', $formid);

        $response = $this->get_http()->request('POST', '/testimonials.php', [
            'query' => [
                'action' => 'testimonial_write',
            ],
            'body' => [
                'formid' => $formid,
                'nickname' => 'Fixture',
                'text' => self::TESTIMONIAL_TEXT,
            ],
        ]);

        $final_url = (string) ($response->getInfo('url') ?? '');
        $this->assertStringContainsString('testimonials.php', $final_url);
        $this->assertStringContainsString('Fixture', $response->getContent(false));
    }

}
