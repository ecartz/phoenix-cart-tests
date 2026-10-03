<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_orders_lookup;
use PhoenixCart\Tests\support\http_review_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class account_gdpr_test extends http_test_case {

    private const PEARS_PRODUCT_ID = 3;

    private const REVIEW_TEXT = 'Phoenix HTTP GDPR fixture review body';

    protected function tearDown(): void {
        http_review_fixture_sql::delete_reviews_for_fixture_customer_product(self::PEARS_PRODUCT_ID);
        http_review_fixture_sql::restore_allow_all_reviews();
        parent::tearDown();
    }

    public function test_gdpr_page_lists_orders_and_reviews_after_cod_and_review(): void {
        http_review_fixture_sql::enable_allow_all_reviews();
        http_review_fixture_sql::delete_reviews_for_fixture_customer_product(self::PEARS_PRODUCT_ID);

        $this->login_fixture_customer();
        $this->complete_cod_checkout_for_pears();
        $this->submit_pears_review();

        $orders_id = http_orders_lookup::latest_orders_id_for_email(self::FIXTURE_CUSTOMER_EMAIL);
        $this->assertGreaterThan(0, $orders_id);

        $gdpr = $this->get_http()->request('GET', '/gdpr.php');
        $this->assertSame(200, $gdpr->getStatusCode());
        $gdpr_body = $gdpr->getContent(false);
        $this->assertStringContainsString('cm-gdpr-orders', $gdpr_body);
        $this->assertStringContainsString((string) $orders_id, $gdpr_body);
        $this->assertStringContainsString('cm-gdpr-reviews', $gdpr_body);
        $this->assertStringContainsString('Pears', $gdpr_body);
    }

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

    private function complete_cod_checkout_for_pears(): void {
        $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => (string) self::PEARS_PRODUCT_ID,
            ],
        ]);

        $shipping_page = $this->get_http()->request('GET', '/checkout_shipping.php');
        $shipping_formid = self::parse_hidden_input($shipping_page->getContent(false), 'formid');

        $payment_page = $this->get_http()->request('POST', '/checkout_shipping.php', [
            'body' => [
                'action' => 'process',
                'formid' => $shipping_formid,
                'shipping' => 'flat_flat',
            ],
        ]);
        $payment_formid = self::parse_hidden_input($payment_page->getContent(false), 'formid');

        $confirmation_page = $this->get_http()->request('POST', '/checkout_confirmation.php', [
            'body' => [
                'formid' => $payment_formid,
                'payment' => 'cod',
            ],
        ]);
        $confirm_formid = self::parse_hidden_input($confirmation_page->getContent(false), 'formid');

        $this->get_http()->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);
    }

    private function submit_pears_review(): void {
        $write_page = $this->get_http()->request('GET', '/ext/modules/content/reviews/write.php', [
            'query' => [
                'products_id' => (string) self::PEARS_PRODUCT_ID,
            ],
        ]);
        $formid = self::parse_hidden_input($write_page->getContent(false), 'formid');

        $this->get_http()->request('POST', '/ext/modules/content/reviews/write.php', [
            'query' => [
                'products_id' => (string) self::PEARS_PRODUCT_ID,
            ],
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'nickname' => 'Fixture',
                'review' => self::REVIEW_TEXT,
                'rating' => '5',
            ],
        ]);
    }

}
