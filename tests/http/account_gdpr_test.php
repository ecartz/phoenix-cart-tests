<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class account_gdpr_test extends http_test_case {

    public function test_logged_in_customer_sees_account_and_gdpr_pages(): void {
        $this->login_fixture_customer();

        $account = $this->get_http()->request('GET', '/account.php');
        $this->assertSame(200, $account->getStatusCode());
        $account_body = $account->getContent(false);
        $this->assertStringContainsString('cm-account-title', $account_body);
        $this->assertStringNotContainsString('login.php', (string) $account->getInfo('url'));

        $gdpr = $this->get_http()->request('GET', '/gdpr.php');
        $this->assertSame(200, $gdpr->getStatusCode());
        $gdpr_body = $gdpr->getContent(false);
        $this->assertStringContainsString('cm-gdpr-intro', $gdpr_body);
    }

    public function test_logged_in_customer_can_download_gdpr_json_export(): void {
        $this->login_fixture_customer();

        $export = $this->get_http()->request('GET', '/gdpr.php', [
            'query' => [
                'action' => 'gdpr_data',
            ],
        ]);
        $this->assertSame(200, $export->getStatusCode());

        $body = $export->getContent(false);
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        $this->assertSame('Phoenix', $data['US']['NAME'] ?? null);
    }

}
