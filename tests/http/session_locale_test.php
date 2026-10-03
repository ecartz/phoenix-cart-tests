<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class session_locale_test extends http_test_case {

    private const PEARS_PRODUCT_ID = 3;

    public function test_currency_query_switches_price_symbol_on_product_page(): void {
        $eur_page = $this->get_http()->request('GET', '/product_info.php', [
            'query' => [
                'products_id' => (string) self::PEARS_PRODUCT_ID,
                'currency' => 'EUR',
            ],
        ]);
        $this->assertSame(200, $eur_page->getStatusCode());
        $eur_body = $eur_page->getContent(false);
        $this->assertStringContainsString('Selected Currency: EUR', $eur_body);
        $this->assertStringContainsString('data-product-price="4.25"', $eur_body);

        $usd_page = $this->get_http()->request('GET', '/product_info.php', [
            'query' => [
                'products_id' => (string) self::PEARS_PRODUCT_ID,
                'currency' => 'USD',
            ],
        ]);
        $this->assertSame(200, $usd_page->getStatusCode());
        $usd_body = $usd_page->getContent(false);
        $this->assertStringContainsString('Selected Currency: USD', $usd_body);
        $this->assertStringContainsString('$4.99', $usd_body);
    }

    public function test_language_query_keeps_english_and_falls_back_for_unknown_code(): void {
        $english = $this->get_http()->request('GET', '/', [
            'query' => [
                'language' => 'en',
            ],
        ]);
        $this->assertSame(200, $english->getStatusCode());
        $this->assertStringContainsString('Our Farm', $english->getContent(false));

        $fallback = $this->get_http()->request('GET', '/', [
            'query' => [
                'language' => 'zz',
            ],
        ]);
        $this->assertSame(200, $fallback->getStatusCode());
        $this->assertStringContainsString('Our Farm', $fallback->getContent(false));
    }

}
