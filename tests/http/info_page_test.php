<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class info_page_test extends http_test_case {

    #[DataProvider('install_info_page_provider')]
    public function test_info_php_shows_install_seed_page(
        string $pages_id,
        string $title,
        string $text_snippet
    ): void {
        $response = $this->get_http()->request('GET', '/info.php', [
            'query' => [
                'pages_id' => $pages_id,
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());

        $body = $response->getContent(false);
        $this->assertStringContainsString($title, $body);
        $this->assertStringContainsString($text_snippet, $body);
    }

    public static function install_info_page_provider(): array {
        return [
            'privacy' => ['1', 'Privacy & Cookie Policy', 'Privacy/Cookie Policies Text'],
            'conditions' => ['2', 'Terms & Conditions', 'Terms & Conditions Text'],
            'shipping' => ['3', 'Shipping & Returns', 'Shipping & Returns Text'],
        ];
    }

    public function test_cookie_usage_php_shows_slug_page_from_seed(): void {
        $response = $this->get_http()->request('GET', '/cookie_usage.php');

        $this->assertSame(200, $response->getStatusCode());

        $body = $response->getContent(false);
        $this->assertStringContainsString('Cookie Usage', $body);
        $this->assertStringContainsString('Cookie Privacy and Security', $body);
    }

    public function test_ssl_check_php_shows_slug_page_from_seed(): void {
        $response = $this->get_http()->request('GET', '/ssl_check.php');

        $this->assertSame(200, $response->getStatusCode());

        $body = $response->getContent(false);
        $this->assertStringContainsString('Security Check', $body);
        $this->assertStringContainsString('Privacy and Security', $body);
    }

}
