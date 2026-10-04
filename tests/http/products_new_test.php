<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class products_new_test extends http_test_case {

    public function test_products_new_lists_sample_catalog_with_sortable_listing(): void {
        $response = $this->get_http()->request('GET', '/products_new.php');

        $this->assertSame(200, $response->getStatusCode());
        $body = $response->getContent(false);
        $this->assertStringContainsString('is-product', $body);
        $this->assertTrue(
            str_contains($body, 'Oranges') || str_contains($body, 'Pears'),
            'products_new should list sample catalog items'
        );
    }

}
