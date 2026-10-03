<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\content;

use cm_pi_review_stars;
use PhoenixCart\Tests\support\content_module_test_case;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

final class cm_pi_review_stars_test extends content_module_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->with_linker();
        $this->define_constants([
            'STAR_RATING' => 'Rated %s Stars',
            'MODULE_CONTENT_PI_REVIEW_STARS_STATUS' => 'True',
            'MODULE_CONTENT_PI_REVIEW_STARS_CONTENT_WIDTH' => 'col-sm-12',
            'MODULE_CONTENT_PI_REVIEW_STARS_COUNT' => '%d reviews',
            'MODULE_CONTENT_PI_REVIEW_STARS_COUNT_ONE' => '%d review',
            'MODULE_CONTENT_PI_REVIEW_STARS_DO_REVIEW' => 'Write a review',
            'MODULE_CONTENT_PI_REVIEW_STARS_DO_FIRST_REVIEW' => 'Write the first review',
        ]);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_buffers_star_rating_and_review_link_when_product_has_reviews(): void {
        $GLOBALS['product'] = new class {

            public function get(string $key) {
                return match ($key) {
                    'id' => 3,
                    'reviews' => [1],
                    'review_rating' => 4.0,
                    default => null,
                };
            }

        };

        $this->execute_module(cm_pi_review_stars::class);

        $content = $this->buffered_content('product_info');
        $this->assertStringContainsString('cm-pi-review-stars', $content);
        $this->assertStringContainsString('1 review', $content);
        $this->assertStringContainsString('write.php', $content);
        $this->assertStringContainsString('Write a review', $content);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_execute_prompts_for_first_review_when_product_has_none(): void {
        $GLOBALS['product'] = new class {

            public function get(string $key) {
                return match ($key) {
                    'id' => 3,
                    'reviews' => [],
                    'review_rating' => 0.0,
                    default => null,
                };
            }

        };

        $this->reset_template();
        $this->execute_module(cm_pi_review_stars::class);

        $content = $this->buffered_content('product_info');
        $this->assertStringContainsString('0 reviews', $content);
        $this->assertStringContainsString('Write the first review', $content);
    }

}
