<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Integration;

use info_pages;
use PhoenixCart\Tests\Support\mysql_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('mysql')]
final class info_pages_test extends mysql_test_case
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION['languages_id'] = 1;
    }

    public function test_requirements_finds_install_slugs(): void
    {
        $missing = info_pages::requirements(['conditions', 'privacy', 'shipping']);

        $this->assertSame([], array_values($missing));
    }

    public function test_get_element_reads_privacy_text_from_join(): void
    {
        $text = info_pages::getElement(
            ['p.slug' => 'privacy', 'pd.languages_id' => '1'],
            'pages_text'
        );

        $this->assertStringContainsString('Privacy/Cookie Policies Text', (string) $text);
    }

    public function test_get_container_returns_privacy_row_for_english(): void
    {
        $rows = info_pages::getContainer([
            'pd.languages_id' => '1',
            'p.slug' => 'privacy',
        ]);

        $this->assertCount(1, $rows);
        $this->assertSame('privacy', $rows[0]['slug'] ?? null);
        $this->assertSame('Privacy & Cookie Policy', $rows[0]['pages_title'] ?? null);
    }
}
