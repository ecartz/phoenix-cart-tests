<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Group('installer')]
final class admin_catalog_test extends install_test_case
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_dashboard_renders(): void
    {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/index.php', [], 'display-4');
    }

    public function test_admin_customers_list_renders(): void
    {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/customers.php', [], 'display-4');
    }

    public function test_admin_orders_list_renders(): void
    {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/orders.php', [], 'display-4');
    }

    public function test_admin_catalog_list_renders(): void
    {
        $admin_http = $this->login_installed_admin();
        $this->assert_admin_get_page($admin_http, '/admin/catalog.php', ['cPath' => '1_3'], 'Pears');
    }

    public function test_admin_catalog_toggles_product_status(): void
    {
        $admin_http = $this->login_installed_admin();
        $catalog_html = $this->fetch_catalog_html_with_active_product($admin_http);
        $disable_href = $this->extract_set_flag_href($catalog_html, '0');
        $this->assertNotSame('', $disable_href);
        $catalog_query = $this->admin_query_from_href($disable_href);

        $toggle = $admin_http->request('GET', $this->admin_path_from_href($disable_href), [
            'query' => $catalog_query,
        ]);
        $this->assertContains($toggle->getStatusCode(), [200, 302]);

        $after_disable = $this->fetch_catalog_page($admin_http, $catalog_query);
        $this->assertStringContainsString('fa-times-circle text-danger', $after_disable);
        $this->assertStringContainsString('flag=1', $after_disable);

        $enable_href = $this->extract_set_flag_href($after_disable, '1');
        $this->assertNotSame('', $enable_href);

        $restore = $admin_http->request('GET', $this->admin_path_from_href($enable_href), [
            'query' => $this->admin_query_from_href($enable_href),
        ]);
        $this->assertContains($restore->getStatusCode(), [200, 302]);

        $after_enable = $this->fetch_catalog_page($admin_http, $catalog_query);
        $this->assertStringContainsString('fa-check-circle text-success', $after_enable);
    }

    /**
     * @param array<string, string> $catalog_query
     */
    private function fetch_catalog_page(HttpClientInterface $admin_http, array $catalog_query): string
    {
        $list_query = $catalog_query;
        unset($list_query['action'], $list_query['flag']);
        $response = $admin_http->request('GET', '/admin/catalog.php', ['query' => $list_query]);
        $this->assertSame(200, $response->getStatusCode());

        return $response->getContent(false);
    }

    private function fetch_catalog_html_with_active_product(HttpClientInterface $admin_http): string
    {
        $paths_to_try = [
            ['cPath' => '1_3'],
            ['cPath' => '1'],
            [],
        ];

        $last_html = '';
        foreach ($paths_to_try as $query) {
            $response = $admin_http->request('GET', '/admin/catalog.php', ['query' => $query]);
            $this->assertSame(200, $response->getStatusCode());
            $last_html = $response->getContent(false);
            if (str_contains($last_html, 'action=set_flag') && str_contains($last_html, 'flag=0')) {
                return $last_html;
            }
        }

        if (preg_match('/href="([^"]*catalog\.php\?[^"]*cPath=[^"]+)"/', $last_html, $category_link) === 1) {
            $nested = $admin_http->request('GET', $this->admin_path_from_href($category_link[1]), [
                'query' => $this->admin_query_from_href($category_link[1]),
            ]);
            $this->assertSame(200, $nested->getStatusCode());
            $nested_html = $nested->getContent(false);
            $this->assertStringContainsString('action=set_flag', $nested_html);

            return $nested_html;
        }

        $this->fail('catalog.php did not list a product with an active status toggle');
    }

    private function extract_set_flag_href(string $html, string $flag): string
    {
        $pattern = '/href="([^"]*action=set_flag[^"]*flag=' . preg_quote($flag, '/') . '[^"]*)"/';
        if (preg_match($pattern, $html, $matches) !== 1) {
            return '';
        }

        return html_entity_decode($matches[1], ENT_QUOTES);
    }

    /**
     * @return array<string, string>
     */
    private function admin_query_from_href(string $href): array
    {
        $query_string = parse_url($href, PHP_URL_QUERY);
        if (!is_string($query_string) || $query_string === '') {
            return [];
        }

        $query = [];
        parse_str($query_string, $query);

        return array_map(static fn ($value) => (string) $value, $query);
    }

    private function admin_path_from_href(string $href): string
    {
        $path = parse_url($href, PHP_URL_PATH);
        if (is_string($path) && str_starts_with($path, '/admin/')) {
            return $path;
        }

        if (str_starts_with($href, 'catalog.php')) {
            return '/admin/catalog.php';
        }

        return '/admin/' . ltrim($href, '/');
    }
}
