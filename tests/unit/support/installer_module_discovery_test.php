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
<table class="table table-striped table-hover">
<thead class="table-dark"><tr><th>Modules</th></tr></thead>
<tbody>
<tr onclick="document.location.href='modules.php?set=payment&amp;module=cod'"><td>COD</td></tr>
</tbody>
</table>
HTML;

        $this->assertSame('cod', self::parse_new_module_code_from_modules_html($html));
    }

    public function test_parse_new_module_codes_merges_install_form_and_table_links(): void {
        $html = <<<'HTML'
<table class="table table-striped table-hover">
<thead class="table-dark"><tr><th>Modules</th></tr></thead>
<tbody>
<tr onclick="document.location.href='modules.php?set=payment&amp;module=cod'"><td>COD</td></tr>
<tr onclick="document.location.href='modules.php?set=payment&amp;module=stripe'"><td>Stripe</td></tr>
</tbody>
</table>
<form name="install_module" action="modules.php?set=payment&amp;module=cod&amp;action=install" method="post">
HTML;

        $this->assertSame(
            ['cod', 'stripe'],
            self::parse_new_module_codes_from_modules_html($html),
        );
    }

    public function test_module_list_needle_decodes_amp_in_installed_row_onclick(): void {
        $html = <<<'HTML'
<table class="table table-striped table-hover">
<thead class="table-dark"><tr><th>Modules</th></tr></thead>
<tbody>
<tr onclick="document.location.href='modules.php?set=payment&amp;module=pm2checkout'"><td>Pay</td></tr>
</tbody>
</table>
HTML;

        $this->assertSame(
            'pm2checkout',
            $this->module_list_needle_for_test($html, 'pm2checkout'),
        );
    }

    private function module_list_needle_for_test(string $html, string $module_code): string
    {
        return $this->admin_module_list_needle(
            $this->admin_list_html_for_needle_assertion($html),
            $module_code,
        );
    }
}
