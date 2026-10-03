<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_review_fixture_sql;
use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class product_review_test extends http_test_case {

    private const PEARS_PRODUCT_ID = 3;

    private const REVIEW_TEXT = 'Phoenix HTTP fixture review body';

    protected function tearDown(): void {
        http_review_fixture_sql::delete_reviews_for_fixture_customer_product(self::PEARS_PRODUCT_ID);
        http_review_fixture_sql::restore_allow_all_reviews();
        parent::tearDown();
    }

    public function test_logged_in_customer_can_submit_product_review(): void {
        http_review_fixture_sql::enable_allow_all_reviews();
        http_review_fixture_sql::delete_reviews_for_fixture_customer_product(self::PEARS_PRODUCT_ID);

        $this->login_fixture_customer();

        $write_page = $this->get_http()->request('GET', '/ext/modules/content/reviews/write.php', [
            'query' => [
                'products_id' => (string) self::PEARS_PRODUCT_ID,
            ],
        ]);
        $this->assertSame(200, $write_page->getStatusCode());
        $write_html = $write_page->getContent(false);
        $formid = self::parse_hidden_input($write_html, 'formid');
        $this->assertNotSame('', $formid);

        $response = $this->get_http()->request('POST', '/ext/modules/content/reviews/write.php', [
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

        $final_url = (string) ($response->getInfo('url') ?? '');
        $this->assertStringContainsString('product_info.php', $final_url);
        $this->assertStringContainsString(
            'Thank you for your review',
            $response->getContent(false)
        );
    }

}
