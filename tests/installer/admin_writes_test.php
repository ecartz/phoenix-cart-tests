<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Group('installer')]
final class admin_writes_test extends install_test_case
{
    private const NEWSLETTER_TITLE = 'Phoenix Installer Draft Newsletter';

    private const NEWSLETTER_CONTENT = 'Installer acceptance test newsletter body.';

    private const ADDRESS_BOOK_MAX_ORIGINAL = '5';

    private const ADDRESS_BOOK_MAX_CHANGED = '6';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_reviews_toggles_status(): void
    {
        $admin_http = $this->login_installed_admin();
        $reviews_html = $this->fetch_reviews_list($admin_http);
        $this->assertStringContainsString('John Doe', $reviews_html);
        $this->assertStringContainsString('fa-check-circle text-success', $reviews_html);

        $disable_href = $this->extract_set_flag_href($reviews_html, '0');
        $this->assertNotSame('', $disable_href);

        $toggle = $admin_http->request('GET', $this->admin_path_from_href($disable_href), [
            'query' => $this->admin_query_from_href($disable_href),
        ]);
        $this->assertContains($toggle->getStatusCode(), [200, 302]);

        $after_disable = $this->fetch_reviews_list($admin_http);
        $this->assertStringContainsString('fa-times-circle text-danger', $after_disable);

        $enable_href = $this->extract_set_flag_href($after_disable, '1');
        $this->assertNotSame('', $enable_href);

        $restore = $admin_http->request('GET', $this->admin_path_from_href($enable_href), [
            'query' => $this->admin_query_from_href($enable_href),
        ]);
        $this->assertContains($restore->getStatusCode(), [200, 302]);

        $after_enable = $this->fetch_reviews_list($admin_http);
        $this->assertStringContainsString('fa-check-circle text-success', $after_enable);
    }

    public function test_admin_newsletter_draft_insert_and_delete(): void
    {
        $admin_http = $this->login_installed_admin();

        $new_page = $admin_http->request('GET', '/admin/newsletters.php', [
            'query' => ['action' => 'new'],
        ]);
        $this->assertSame(200, $new_page->getStatusCode());
        $new_html = $new_page->getContent(false);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);

        $insert = $admin_http->request('POST', '/admin/newsletters.php', [
            'query' => ['action' => 'insert'],
            'body' => [
                'formid' => $formid,
                'module' => 'newsletter',
                'title' => self::NEWSLETTER_TITLE,
                'content' => self::NEWSLETTER_CONTENT,
            ],
        ]);
        $this->assertContains($insert->getStatusCode(), [200, 302]);

        $list_after_insert = $this->fetch_newsletters_list($admin_http);
        $this->assertStringContainsString(self::NEWSLETTER_TITLE, $list_after_insert);
        $newsletter_id = $this->parse_newsletter_id_from_list($list_after_insert, self::NEWSLETTER_TITLE);

        $list_formid = self::parse_formid_from_page($list_after_insert);
        if ($list_formid === '') {
            $selected = $admin_http->request('GET', '/admin/newsletters.php', [
                'query' => ['nID' => $newsletter_id],
            ]);
            $this->assertSame(200, $selected->getStatusCode());
            $list_formid = self::parse_formid_from_page($selected->getContent(false));
        }
        $this->assertNotSame('', $list_formid);

        $delete = $admin_http->request('POST', '/admin/newsletters.php', [
            'query' => [
                'action' => 'delete_confirm',
                'nID' => $newsletter_id,
            ],
            'body' => [
                'formid' => $list_formid,
            ],
        ]);
        $this->assertContains($delete->getStatusCode(), [200, 302]);

        $list_after_delete = $this->fetch_newsletters_list($admin_http);
        $this->assertStringNotContainsString(self::NEWSLETTER_TITLE, $list_after_delete);
    }

    public function test_admin_boxes_module_install_and_remove(): void
    {
        $admin_http = $this->login_installed_admin();

        $new_modules = $admin_http->request('GET', '/admin/modules.php', [
            'query' => [
                'set' => 'boxes',
                'list' => 'new',
                'module' => 'bm_categories',
            ],
        ]);
        $this->assertSame(200, $new_modules->getStatusCode());
        $new_html = $new_modules->getContent(false);
        $this->assertStringContainsString('bm_categories', $new_html);
        $install_formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $install_formid);

        $install = $admin_http->request('POST', '/admin/modules.php', [
            'query' => [
                'set' => 'boxes',
                'action' => 'install',
                'module' => 'bm_categories',
            ],
            'body' => [
                'formid' => $install_formid,
            ],
        ]);
        $this->assertContains($install->getStatusCode(), [200, 302]);

        $installed_list = $admin_http->request('GET', '/admin/modules.php', [
            'query' => ['set' => 'boxes'],
        ]);
        $this->assertSame(200, $installed_list->getStatusCode());
        $installed_html = $installed_list->getContent(false);
        $this->assertStringContainsString('bm_categories', $installed_html);

        $remove_page = $admin_http->request('GET', '/admin/modules.php', [
            'query' => [
                'set' => 'boxes',
                'module' => 'bm_categories',
            ],
        ]);
        $this->assertSame(200, $remove_page->getStatusCode());
        $remove_html = $remove_page->getContent(false);
        $remove_formid = self::parse_hidden_input($remove_html, 'formid');
        $this->assertNotSame('', $remove_formid);

        $remove = $admin_http->request('POST', '/admin/modules.php', [
            'query' => [
                'set' => 'boxes',
                'action' => 'remove',
                'module' => 'bm_categories',
            ],
            'body' => [
                'formid' => $remove_formid,
            ],
        ]);
        $this->assertContains($remove->getStatusCode(), [200, 302]);

        $after_remove = $admin_http->request('GET', '/admin/modules.php', [
            'query' => ['set' => 'boxes'],
        ]);
        $this->assertSame(200, $after_remove->getStatusCode());
        $this->assertStringNotContainsString('bm_categories', $after_remove->getContent(false));
    }

    public function test_admin_max_address_book_entries_configuration_round_trip(): void
    {
        $admin_http = $this->login_installed_admin();
        $configuration_id = $this->resolve_address_book_max_configuration_id($admin_http);
        $this->assertNotSame('', $configuration_id);

        $this->post_configuration_value(
            $admin_http,
            '3',
            $configuration_id,
            self::ADDRESS_BOOK_MAX_CHANGED,
        );
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/configuration.php',
            ['gID' => '3'],
            self::ADDRESS_BOOK_MAX_CHANGED,
        );

        $this->post_configuration_value(
            $admin_http,
            '3',
            $configuration_id,
            self::ADDRESS_BOOK_MAX_ORIGINAL,
        );
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/configuration.php',
            ['gID' => '3'],
            self::ADDRESS_BOOK_MAX_ORIGINAL,
        );
    }

    private function fetch_reviews_list(HttpClientInterface $admin_http): string
    {
        $response = $admin_http->request('GET', '/admin/reviews.php');
        $this->assertSame(200, $response->getStatusCode());

        return $response->getContent(false);
    }

    private function fetch_newsletters_list(HttpClientInterface $admin_http): string
    {
        $response = $admin_http->request('GET', '/admin/newsletters.php');
        $this->assertSame(200, $response->getStatusCode());

        return $response->getContent(false);
    }

    private function parse_newsletter_id_from_list(string $html, string $title): string
    {
        if (!str_contains($html, $title)) {
            $this->fail('newsletters.php list did not contain the draft title');
        }

        if (preg_match('/[?&]nID=(\d+)/', $html, $matches) === 1) {
            return $matches[1];
        }

        $this->fail('newsletters.php did not expose nID for the draft newsletter');
    }

    private function resolve_address_book_max_configuration_id(HttpClientInterface $admin_http): string
    {
        $list = $admin_http->request('GET', '/admin/configuration.php', [
            'query' => ['gID' => '3'],
        ]);
        $this->assertSame(200, $list->getStatusCode());
        $html = $list->getContent(false);
        $this->assertStringContainsString(self::ADDRESS_BOOK_MAX_ORIGINAL, $html);

        if (preg_match_all(
            '/href="([^"]*configuration\.php\?[^"]*cID=(\d+)[^"]*)"/',
            $html,
            $matches,
            PREG_SET_ORDER,
        ) === false) {
            return '';
        }

        foreach ($matches as $match) {
            $configuration_id = $match[2];
            $query = $this->admin_query_from_href($match[1]);
            $query['action'] = 'edit';
            $detail = $admin_http->request('GET', $this->admin_path_from_href($match[1]), [
                'query' => $query,
            ]);
            if ($detail->getStatusCode() !== 200) {
                continue;
            }

            $edit_html = $detail->getContent(false);
            if (str_contains($edit_html, 'configuration_value')
                && str_contains($edit_html, 'value="' . self::ADDRESS_BOOK_MAX_ORIGINAL . '"')) {
                return $configuration_id;
            }
        }

        return '';
    }

    private function post_configuration_value(
        HttpClientInterface $admin_http,
        string $group_id,
        string $configuration_id,
        string $value,
    ): void {
        $edit = $admin_http->request('GET', '/admin/configuration.php', [
            'query' => [
                'gID' => $group_id,
                'cID' => $configuration_id,
                'action' => 'edit',
            ],
        ]);
        $this->assertSame(200, $edit->getStatusCode());
        $edit_html = $edit->getContent(false);
        $formid = self::parse_hidden_input($edit_html, 'formid');
        $this->assertNotSame('', $formid);

        $save = $admin_http->request('POST', '/admin/configuration.php', [
            'query' => [
                'gID' => $group_id,
                'cID' => $configuration_id,
                'action' => 'save',
            ],
            'body' => [
                'formid' => $formid,
                'configuration_value' => $value,
            ],
        ]);
        $this->assertContains($save->getStatusCode(), [200, 302]);
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

        return array_map(static fn ($item) => (string) $item, $query);
    }

    private function admin_path_from_href(string $href): string
    {
        $path = parse_url($href, PHP_URL_PATH);
        if (is_string($path) && str_starts_with($path, '/admin/')) {
            return $path;
        }

        if (str_starts_with($href, 'reviews.php')) {
            return '/admin/reviews.php';
        }

        if (str_starts_with($href, 'configuration.php')) {
            return '/admin/configuration.php';
        }

        return '/admin/' . ltrim($href, '/');
    }
}
