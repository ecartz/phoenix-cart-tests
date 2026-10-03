<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Group('installer')]
final class admin_merchandising_test extends install_test_case
{
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_advert_manager_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/advert_manager.php', [], 'Our Farm');
    }

    public function test_admin_products_attributes_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/products_attributes.php', [], 'Box Size');
    }

    public function test_admin_products_expected_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/products_expected.php', [], 'Grapefruit');
    }

    public function test_admin_testimonials_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/testimonials.php', [], 'John Doe');
    }

    public function test_admin_info_pages_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/info_pages.php', [], 'Privacy & Cookie Policy');
    }

    public function test_admin_customer_data_groups_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/customer_data_groups.php',
            [],
            'Your Personal Information',
        );
    }

    public function test_admin_order_total_modules_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/modules.php',
            ['set' => 'order_total'],
            'Sub-Total',
        );
    }

    public function test_admin_advert_manager_toggles_status(): void {
        $admin_http = $this->login_installed_admin();
        $advert_html = $this->fetch_advert_html_with_active_advert($admin_http);
        $disable_href = $this->extract_set_flag_href($advert_html, '0');
        $this->assertNotSame('', $disable_href);

        $toggle = $admin_http->request('GET', $this->admin_path_from_href($disable_href), [
            'query' => $this->admin_query_from_href($disable_href),
        ]);
        $this->assertContains($toggle->getStatusCode(), [200, 302]);

        $after_disable = $this->fetch_advert_list($admin_http);
        $this->assertStringContainsString('fa-times-circle text-danger', $after_disable);
        $this->assertStringContainsString('flag=1', $after_disable);

        $enable_href = $this->extract_set_flag_href($after_disable, '1');
        $this->assertNotSame('', $enable_href);

        $restore = $admin_http->request('GET', $this->admin_path_from_href($enable_href), [
            'query' => $this->admin_query_from_href($enable_href),
        ]);
        $this->assertContains($restore->getStatusCode(), [200, 302]);

        $after_enable = $this->fetch_advert_list($admin_http);
        $this->assertStringContainsString('fa-check-circle text-success', $after_enable);
    }

    private function fetch_advert_list(HttpClientInterface $admin_http): string {
        $response = $admin_http->request('GET', '/admin/advert_manager.php');
        $this->assertSame(200, $response->getStatusCode());

        return $response->getContent(false);
    }

    private function fetch_advert_html_with_active_advert(HttpClientInterface $admin_http): string {
        $html = $this->fetch_advert_list($admin_http);
        $this->assertStringContainsString('Our Farm', $html);
        if (str_contains($html, 'action=set_flag') && str_contains($html, 'flag=0')) {
            return $html;
        }

        $this->fail('advert_manager.php did not list an active advert with a status toggle');
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

        if (str_starts_with($href, 'advert_manager.php')) {
            return '/admin/advert_manager.php';
        }

        return '/admin/' . ltrim($href, '/');
    }
}
