<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use language;
use PhoenixCart\Tests\support\mock_catalog_database;
use PhoenixCart\Tests\support\phoenix_test_case;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

#[Group('mockdb')]
final class language_test extends phoenix_test_case {

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_load_all_indexes_languages_by_code(): void {
        $rows = [
            [
                'id' => '1',
                'name' => 'English',
                'code' => 'en',
                'image' => 'icon.gif',
                'directory' => 'english',
            ],
            [
                'id' => '2',
                'name' => 'Deutsch',
                'code' => 'de',
                'image' => 'icon.gif',
                'directory' => 'german',
            ],
        ];

        (new mock_catalog_database([], ['languages' => $rows]))->install_as_global();

        $this->assertSame(
            [
                'en' => $rows[0],
                'de' => $rows[1],
            ],
            language::load_all()
        );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_constructor_selects_default_language(): void {
        if (!defined('DEFAULT_LANGUAGE')) {
            define('DEFAULT_LANGUAGE', 'en');
        }

        $rows = [
            [
                'id' => '1',
                'name' => 'English',
                'code' => 'en',
                'image' => 'icon.gif',
                'directory' => 'english',
            ],
        ];

        (new mock_catalog_database([], ['languages' => $rows]))->install_as_global();

        $language = new language();

        $this->assertSame($rows[0], $language->language);
        $this->assertSame(['en' => $rows[0]], $language->catalog_languages);
    }

}
