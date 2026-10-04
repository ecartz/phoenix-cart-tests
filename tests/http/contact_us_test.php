<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_action_recorder_fixture_sql;
use PhoenixCart\Tests\support\http_mail_capture;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class contact_us_test extends http_test_case {

    protected function setUp(): void {
        parent::setUp();
        http_action_recorder_fixture_sql::clear_module('ar_contact_us');
        if (http_mail_capture::is_enabled()) {
            http_mail_capture::clear();
        }
    }

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

        if (http_mail_capture::is_enabled()) {
            $mail = http_mail_capture::read_combined();
            $this->assertStringContainsString('visitor@example.com', $mail);
            $this->assertStringContainsString('Fixture enquiry from phoenix-cart-tests HTTP suite.', $mail);
        }
    }

    public function test_bad_formid_keeps_contact_form_without_success_message(): void {
        $page = $this->get_http()->request('GET', '/contact_us.php');
        $this->assertSame(200, $page->getStatusCode());

        $response = $this->get_http()->request('POST', '/contact_us.php', [
            'body' => [
                'action' => 'send',
                'formid' => '00000000000000000000000000000000',
                'name' => 'HTTP Test Visitor',
                'email' => 'visitor@example.com',
                'enquiry' => 'Should not be delivered.',
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getContent(false);
        $this->assertStringContainsString('cm-cu-modular', $body);
        $this->assertStringNotContainsString('Your message has been sent to the Shopowner.', $body);
    }

    public function test_action_recorder_blocks_repeat_enquiry_within_window(): void {
        http_action_recorder_fixture_sql::seed_recent_success('ar_contact_us', '127.0.0.1');

        $page = $this->get_http()->request('GET', '/contact_us.php');
        $formid = self::parse_hidden_input($page->getContent(false), 'formid');
        $this->assertNotSame('', $formid);

        $response = $this->get_http()->request('POST', '/contact_us.php', [
            'body' => [
                'action' => 'send',
                'formid' => $formid,
                'name' => 'HTTP Test Visitor',
                'email' => 'visitor@example.com',
                'enquiry' => 'Should be throttled by action recorder.',
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getContent(false);
        $this->assertStringContainsString('An enquiry has already been sent', $body);
        $this->assertStringNotContainsString('Your message has been sent to the Shopowner.', $body);
    }

}
