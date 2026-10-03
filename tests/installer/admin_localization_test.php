<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Group('installer')]
final class admin_localization_test extends install_test_case {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_currencies_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/currencies.php', [], 'U.S. Dollar');
    }

    public function test_admin_zones_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/zones.php', [], 'Alberta');
    }

    public function test_admin_tax_classes_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/tax_classes.php', [], 'Taxable Goods');
    }

    public function test_admin_tax_rates_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/tax_rates.php', [], 'FL TAX 7.0%');
    }

    public function test_admin_geo_zones_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/geo_zones.php', [], 'Florida');
    }

    public function test_admin_orders_status_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/orders_status.php', [], 'Pending');
    }

    public function test_admin_manufacturers_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/manufacturers.php', [], 'Fiacre');
    }

    public function test_admin_reviews_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/reviews.php', [], 'John Doe');
    }

    public function test_admin_specials_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/specials.php', [], 'Oranges');
    }

    public function test_admin_shipping_modules_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/modules.php',
            ['set' => 'shipping'],
            'Flat Rate',
        );
    }

    public function test_admin_specials_toggles_status(): void {
        $admin_http = $this->login_installed_admin();
        $specials_html = $this->fetch_specials_html_with_active_special($admin_http);
        $disable_href = $this->extract_set_flag_href($specials_html, '0');
        $this->assertNotSame('', $disable_href);

        $toggle = $admin_http->request('GET', $this->admin_path_from_href($disable_href), [
            'query' => $this->admin_query_from_href($disable_href),
        ]);
        $this->assertContains($toggle->getStatusCode(), [200, 302]);

        $after_disable = $this->fetch_specials_list($admin_http);
        $this->assertStringContainsString('fa-times-circle text-danger', $after_disable);
        $this->assertStringContainsString('flag=1', $after_disable);

        $enable_href = $this->extract_set_flag_href($after_disable, '1');
        $this->assertNotSame('', $enable_href);

        $restore = $admin_http->request('GET', $this->admin_path_from_href($enable_href), [
            'query' => $this->admin_query_from_href($enable_href),
        ]);
        $this->assertContains($restore->getStatusCode(), [200, 302]);

        $after_enable = $this->fetch_specials_list($admin_http);
        $this->assertStringContainsString('fa-check-circle text-success', $after_enable);
    }

    private function fetch_specials_list(HttpClientInterface $admin_http): string {
        $response = $admin_http->request('GET', '/admin/specials.php');
        $this->assertSame(200, $response->getStatusCode());

        return $response->getContent(false);
    }

    private function fetch_specials_html_with_active_special(HttpClientInterface $admin_http): string {
        $html = $this->fetch_specials_list($admin_http);
        $this->assertStringContainsString('Oranges', $html);
        if (str_contains($html, 'action=set_flag') && str_contains($html, 'flag=0')) {
            return $html;
        }

        $this->fail('specials.php did not list an active special with a status toggle');
    }

    private function extract_set_flag_href(string $html, string $flag): string {
        $pattern = '/href="([^"]*action=set_flag[^"]*flag=' . preg_quote($flag, '/') . '[^"]*)"/';
        if (preg_match($pattern, $html, $matches) !== 1) {
            return '';
        }

        return html_entity_decode($matches[1], ENT_QUOTES);
    }

    /**
     * @return array<string, string>
     */
    private function admin_query_from_href(string $href): array {
        $query_string = parse_url($href, PHP_URL_QUERY);
        if (!is_string($query_string) || $query_string === '') {
            return [];
        }

        $query = [];
        parse_str($query_string, $query);

        return array_map(static fn ($value) => (string) $value, $query);
    }

    private function admin_path_from_href(string $href): string {
        $path = parse_url($href, PHP_URL_PATH);
        if (is_string($path) && str_starts_with($path, '/admin/')) {
            return $path;
        }

        if (str_starts_with($href, 'specials.php')) {
            return '/admin/specials.php';
        }

        return '/admin/' . ltrim($href, '/');
    }

}
