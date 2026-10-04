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
        $this->assertStringContainsString('name="keywords"', $advanced_body);
        $this->assertStringContainsString('name="manufacturers_id"', $advanced_body);

        $search = $this->get_http()->request('GET', '/advanced_search_result.php', [
            'query' => [
                'keywords' => 'Oranges',
            ],
        ]);
        $this->assertSame(200, $search->getStatusCode());
        $search_body = $search->getContent(false);
        $this->assertStringContainsString('Oranges', $search_body);
        $this->assertStringContainsString('cm-asr-title', $search_body);
        $this->assertStringContainsString('is-product', $search_body);

        $specials = $this->get_http()->request('GET', '/specials.php');
        $this->assertSame(200, $specials->getStatusCode());
        $this->assertStringContainsString('Oranges', $specials->getContent(false));

        $new_products = $this->get_http()->request('GET', '/products_new.php');
        $this->assertSame(200, $new_products->getStatusCode());
        $new_body = $new_products->getContent(false);
        $this->assertStringContainsString('>Oranges</a>', $new_body);
        $this->assertStringContainsString('>Pears</a>', $new_body);

        $testimonials = $this->get_http()->request('GET', '/testimonials.php');
        $this->assertSame(200, $testimonials->getStatusCode());
        $testimonials_body = $testimonials->getContent(false);
        $this->assertStringContainsString('John Doe', $testimonials_body);
        $this->assertStringContainsString('cm-t-title', $testimonials_body);
        $this->assertStringContainsString('cm-t-list', $testimonials_body);

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

        $manufacturers_index = $this->get_http()->request('GET', '/manufacturers.php');
        $this->assertSame(200, $manufacturers_index->getStatusCode());
        $this->assertStringContainsString('Fiacre', $manufacturers_index->getContent(false));
    }

    public function test_advanced_search_category_price_and_description_filters(): void {
        $citrus = $this->advanced_search_body([
            'keywords' => 'Oranges',
            'categories_id' => '4',
        ]);
        $this->assertStringContainsString('>Oranges</a>', $citrus);
        $this->assertStringNotContainsString('>Pears</a>', $citrus);

        $apples_and_pears = $this->advanced_search_body([
            'keywords' => 'Pears',
            'categories_id' => '3',
        ]);
        $this->assertStringContainsString('>Pears</a>', $apples_and_pears);
        $this->assertStringNotContainsString('>Oranges</a>', $apples_and_pears);

        $mid_price = $this->advanced_search_body([
            'pfrom' => '4',
            'pto' => '5',
        ]);
        $this->assertStringContainsString('>Pears</a>', $mid_price);
        $this->assertStringNotContainsString('>Oranges</a>', $mid_price);

        $description = $this->advanced_search_body([
            'keywords' => 'balanced',
            'search_in_description' => '1',
        ]);
        $this->assertStringContainsString('>Oranges</a>', $description);
        $this->assertStringNotContainsString('>Pears</a>', $description);

        $name_only = $this->advanced_search_body([
            'keywords' => 'balanced',
        ]);
        $this->assertStringNotContainsString('>Oranges</a>', $name_only);
    }

    /**
     * @param array<string, string> $query
     */
    private function advanced_search_body(array $query): string {
        $response = $this->get_http()->request('GET', '/advanced_search_result.php', [
            'query' => $query,
        ]);
        $this->assertSame(200, $response->getStatusCode());

        return $response->getContent(false);
    }

}
