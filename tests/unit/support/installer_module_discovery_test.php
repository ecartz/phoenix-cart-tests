<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\support;

use PhoenixCart\Tests\support\installer_admin_writes;
use PhoenixCart\Tests\support\phoenix_test_case;

final class installer_module_discovery_test extends phoenix_test_case {
    use installer_admin_writes;

    public function test_parses_module_code_from_install_module_form_action(): void {
        $html = <<<'HTML'
<form name="install_module" action="modules.php?set=boxes&amp;module=bm_categories&amp;action=install" method="post">
<input name="formid" type="hidden" value="abc">
HTML;

        $this->assertSame(
            'bm_categories',
            self::parse_new_module_code_from_modules_html($html),
        );
    }

    public function test_returns_null_when_new_module_list_has_no_install_form(): void {
        $html = <<<'HTML'
<table class="table table-striped table-hover">
<thead class="table-dark"><tr><th>Modules</th></tr></thead>
<tbody></tbody>
</table>
HTML;

        $this->assertNull(self::parse_new_module_code_from_modules_html($html));
    }

    public function test_falls_back_to_module_query_links_when_present(): void {
        $html = <<<'HTML'
<tr onclick="document.location.href='modules.php?set=payment&amp;module=cod'">
HTML;

        $this->assertSame('cod', self::parse_new_module_code_from_modules_html($html));
    }
}
