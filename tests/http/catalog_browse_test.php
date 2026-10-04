<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class catalog_browse_test extends http_test_case {

    public function test_search_specials_new_testimonials_and_manufacturer_listing(): void {
        $advanced = $this->get_http()->request('GET', '/advanced_search.php');
        $this->assertSame(200, $advanced->getStatusCode());
        $advanced_body = $advanced->getContent(false);
        $this->assertStringContainsString('keywords', $advanced_body);
        $this->assertStringContainsString('advanced_search_result.php', $advanced_body);
        $this->assertStringContainsString('cm-asr-title', $advanced_body);
        $this->assertStringContainsString('name="keywords"', $advanced_body);

        $search = $this->get_http()->request('GET', '/advanced_search_result.php', [
            'query' => [
                'keywords' => 'Oranges',
            ],
        ]);
        $this->assertSame(200, $search->getStatusCode());
        $search_body = $search->getContent(false);
        $this->assertStringContainsString('Oranges', $search_body);
        $this->assertStringContainsString('cm-asr-search-result', $search_body);

        $specials = $this->get_http()->request('GET', '/specials.php');
        $this->assertSame(200, $specials->getStatusCode());
        $this->assertStringContainsString('Oranges', $specials->getContent(false));

        $new_products = $this->get_http()->request('GET', '/products_new.php');
        $this->assertSame(200, $new_products->getStatusCode());
        $new_body = $new_products->getContent(false);
        $this->assertTrue(
            str_contains($new_body, 'Oranges') || str_contains($new_body, 'Pears'),
            'products_new should list sample catalog items'
        );

        $testimonials = $this->get_http()->request('GET', '/testimonials.php');
        $this->assertSame(200, $testimonials->getStatusCode());
        $testimonials_body = $testimonials->getContent(false);
        $this->assertStringContainsString('John Doe', $testimonials_body);
        $this->assertStringContainsString('cm-testimonials', $testimonials_body);

        $manufacturer = $this->get_http()->request('GET', '/index.php', [
            'query' => [
                'manufacturers_id' => '1',
            ],
        ]);
        $this->assertSame(200, $manufacturer->getStatusCode());
        $manufacturer_body = $manufacturer->getContent(false);
        $this->assertStringContainsString('Fiacre', $manufacturer_body);
        $this->assertTrue(
            str_contains($manufacturer_body, 'Oranges') || str_contains($manufacturer_body, 'Pears'),
            'manufacturer listing should include a sample product'
        );
    }

}
