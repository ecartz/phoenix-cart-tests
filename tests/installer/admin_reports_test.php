<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Group('installer')]
final class admin_reports_test extends install_test_case {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_stats_products_purchased_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/stats_products_purchased.php',
            [],
            'Best Products Purchased',
        );
    }

    public function test_admin_stats_customers_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/stats_customers.php',
            [],
            'Best Customer Orders-Total',
        );
    }

    public function test_admin_whos_online_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/whos_online.php', [], "Who's Online");
    }

    public function test_storefront_visit_lists_session_in_whos_online(): void {
        $shop_http = installer_bootstrap::client();
        $home = $shop_http->request('GET', '/');
        $this->assertSame(200, $home->getStatusCode());

        $admin_http = $this->login_installed_admin();
        $online = $admin_http->request('GET', '/admin/whos_online.php');
        $this->assertSame(200, $online->getStatusCode());
        $body = $online->getContent(false);
        $this->assertStringContainsString("Who's Online", $body);
        $this->assertTrue(
            str_contains($body, '127.0.0.1') || str_contains($body, 'Guest'),
            'whos_online should list the storefront session or guest row'
        );
    }

    public function test_admin_action_recorder_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/action_recorder.php', [], 'Action Recorder');
    }

    public function test_admin_store_logo_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/store_logo.php', [], 'Store Logo');
    }

    public function test_admin_newsletters_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/newsletters.php', [], 'Newsletter Manager');
    }

    public function test_admin_content_modules_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/modules.php',
            ['set' => 'content'],
            'cm_account_title',
        );
    }

    public function test_admin_header_tags_modules_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/modules.php',
            ['set' => 'header_tags'],
            'Canonical Links',
        );
    }

    public function test_admin_dashboard_modules_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/modules.php',
            ['set' => 'dashboard'],
            'd_orders',
        );
    }

    public function test_admin_customer_data_modules_list_renders(): void {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/modules.php',
            ['set' => 'customer_data'],
            'E-mail Address',
        );
    }

    public function test_admin_testimonials_toggles_status(): void {
        $admin_http = $this->login_installed_admin();
        $testimonials_html = $this->fetch_testimonials_html_with_active_testimonial($admin_http);
        $disable_href = $this->extract_set_flag_href($testimonials_html, '0');
        $this->assertNotSame('', $disable_href);

        $toggle = $admin_http->request('GET', $this->admin_path_from_href($disable_href), [
            'query' => $this->admin_query_from_href($disable_href),
        ]);
        $this->assertContains($toggle->getStatusCode(), [200, 302]);

        $after_disable = $this->fetch_testimonials_list($admin_http);
        $this->assertStringContainsString('fa-times-circle text-danger', $after_disable);
        $this->assertStringContainsString('flag=1', $after_disable);

        $enable_href = $this->extract_set_flag_href($after_disable, '1');
        $this->assertNotSame('', $enable_href);

        $restore = $admin_http->request('GET', $this->admin_path_from_href($enable_href), [
            'query' => $this->admin_query_from_href($enable_href),
        ]);
        $this->assertContains($restore->getStatusCode(), [200, 302]);

        $after_enable = $this->fetch_testimonials_list($admin_http);
        $this->assertStringContainsString('fa-check-circle text-success', $after_enable);
    }

    private function fetch_testimonials_list(HttpClientInterface $admin_http): string {
        $response = $admin_http->request('GET', '/admin/testimonials.php');
        $this->assertSame(200, $response->getStatusCode());

        return $response->getContent(false);
    }

    private function fetch_testimonials_html_with_active_testimonial(HttpClientInterface $admin_http): string {
        $html = $this->fetch_testimonials_list($admin_http);
        $this->assertStringContainsString('John Doe', $html);
        if (str_contains($html, 'action=set_flag') && str_contains($html, 'flag=0')) {
            return $html;
        }

        $this->fail('testimonials.php did not list an active testimonial with a status toggle');
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

        if (str_starts_with($href, 'testimonials.php')) {
            return '/admin/testimonials.php';
        }

        return '/admin/' . ltrim($href, '/');
    }

}
