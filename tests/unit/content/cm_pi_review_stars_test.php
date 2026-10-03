<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_pi_review_stars;
use PhoenixCart\Tests\support\content_module_test_case;
use Product;

final class cm_pi_review_stars_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->with_linker();
        if (!defined('STAR_RATING')) {
            define('STAR_RATING', 'Rated %s Stars');
        }

        $this->define_constants([
            'MODULE_CONTENT_PI_REVIEW_STARS_STATUS' => 'True',
            'MODULE_CONTENT_PI_REVIEW_STARS_CONTENT_WIDTH' => 'col-sm-12',
            'MODULE_CONTENT_PI_REVIEW_STARS_DO_REVIEW' => 'Write a review',
            'MODULE_CONTENT_PI_REVIEW_STARS_DO_FIRST_REVIEW' => 'Be the first to review this product',
            'MODULE_CONTENT_PI_REVIEW_STARS_COUNT' => '%s Reviews',
            'MODULE_CONTENT_PI_REVIEW_STARS_COUNT_ONE' => '%s Review',
        ]);
    }

    public function test_execute_buffers_review_stars_when_product_has_reviews(): void {
        $product = new Product(['id' => 3, 'status' => 1]);
        $product->set('reviews', [
            ['reviews_id' => 10],
        ]);
        $product->set('review_rating', 4.6);
        $GLOBALS['product'] = $product;

        $this->execute_module(cm_pi_review_stars::class);

        $content = $this->buffered_content('product_info');
        $this->assertStringContainsString('cm-pi-review-stars', $content);
        $this->assertStringContainsString('1 Review', $content);
        $this->assertStringContainsString('write.php', $content);
    }

    public function test_execute_buffers_zero_review_prompt_when_product_has_no_reviews(): void {
        $product = new Product(['id' => 3, 'status' => 1]);
        $product->set('reviews', []);
        $GLOBALS['product'] = $product;

        $this->reset_template();
        $this->execute_module(cm_pi_review_stars::class);

        $content = $this->buffered_content('product_info');
        $this->assertStringContainsString('0 Reviews', $content);
        $this->assertStringContainsString('Be the first to review this product', $content);
    }

}
