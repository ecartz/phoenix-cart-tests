<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Unit\Html;

use star_rating;

final class star_rating_test extends html_test_case {

    protected function setUp(): void {
        parent::setUp();

        $this->define_constant_if_missing('STAR_RATING', 'Rating: %s out of 5');
    }

    public function test_to_string_renders_filled_and_empty_stars(): void {
        $rating = new star_rating(3.4);

        $markup = "$rating";

        $this->assertStringContainsString('class="text-warning"', $markup);
        $this->assertSame(3, substr_count($markup, 'fas fa-star'));
        $this->assertSame(2, substr_count($markup, 'far fa-star'));
        $this->assertStringContainsString('title="Rating: 3.4 out of 5"', $markup);
    }

    public function test_rating_is_capped_at_five_stars(): void {
        $rating = new star_rating(8.0);

        $markup = "$rating";

        $this->assertSame(5, substr_count($markup, 'fas fa-star'));
        $this->assertSame(0, substr_count($markup, 'far fa-star'));
    }
}
