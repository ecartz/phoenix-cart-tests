<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_admin_writes;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Group('installer')]
final class admin_content_writes_test extends install_test_case
{
    use installer_admin_writes;

    private const CUSTOMER_FIRSTNAME = 'Content';

    private const CUSTOMER_LASTNAME = 'Writer';

    private const CUSTOMER_EMAIL = 'phoenix-install-content@example.com';

    private const CUSTOMER_PASSWORD = 'phoenix-install-test';

    private const REVIEW_TEXT = 'Phoenix installer review body.';

    private const TESTIMONIAL_NICK = 'Phoenix Installer Nick';

    private const TESTIMONIAL_TEXT = 'Phoenix installer testimonial body.';

    private const INFO_PAGE_TITLE = 'Phoenix Installer Info Page';

    private const INFO_PAGE_SLUG = 'phoenix-installer-info';

    private const ADVERT_TITLE = 'Phoenix Installer Advert';

    private const OUTGOING_TITLE = 'Phoenix Installer Outgoing Template';

    private const SPECIAL_PRODUCT_ID = '2';

    private const SPECIAL_OFFER_PRICE = '0.42';

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_content_entities_insert_and_delete(): void {
        $shop_http = installer_bootstrap::client();
        $this->register_storefront_customer($shop_http);
        $customer_id = $this->parse_customer_id_from_admin();

        $admin_http = $this->login_installed_admin();

        $this->insert_and_delete_special($admin_http);
        $this->insert_and_delete_review($admin_http, $customer_id);
        $this->insert_and_delete_testimonial($admin_http, $customer_id);
        $this->insert_and_delete_info_page($admin_http);
        $this->insert_and_delete_advert($admin_http);
        $this->insert_and_delete_outgoing_template($admin_http);
    }

    private function insert_and_delete_special(HttpClientInterface $admin_http): void {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/specials.php', ['action' => 'new']);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);

        $expdate = gmdate('Y-m-d', strtotime('+7 days'));

        $this->post_admin_form($admin_http, '/admin/specials.php', ['action' => 'insert'], [
            'formid' => $formid,
            'products_id' => self::SPECIAL_PRODUCT_ID,
            'specials_price' => self::SPECIAL_OFFER_PRICE,
            'expdate' => $expdate,
        ]);

        $list_html = $this->fetch_admin_page($admin_http, '/admin/specials.php');
        $this->assertStringContainsString(self::SPECIAL_OFFER_PRICE, $list_html);
        $special_id = $this->parse_entity_id_near_needle($list_html, self::SPECIAL_OFFER_PRICE, 'sID');

        $this->confirm_admin_delete($admin_http, '/admin/specials.php', 'sID', $special_id);
        $after = $this->fetch_admin_page($admin_http, '/admin/specials.php');
        $this->assertStringNotContainsString(self::SPECIAL_OFFER_PRICE, $after);
    }

    private function insert_and_delete_review(HttpClientInterface $admin_http, string $customer_id): void {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/reviews.php', ['action' => 'new']);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->post_admin_form($admin_http, '/admin/reviews.php', ['action' => 'add_new'], [
            'formid' => $formid,
            'products_id' => self::SPECIAL_PRODUCT_ID,
            'customer_id' => $customer_id,
            'reviews_text' => self::REVIEW_TEXT,
            'reviews_rating' => '5',
        ]);

        $list_html = $this->assert_admin_list_contains($admin_http, '/admin/reviews.php', [], 'Lemons');
        $review_id = $this->parse_entity_id_near_needle($list_html, 'Lemons', 'rID');

        $this->confirm_admin_delete($admin_http, '/admin/reviews.php', 'rID', $review_id);
        $this->assert_admin_list_not_contains($admin_http, '/admin/reviews.php', [], 'Lemons');
    }

    private function insert_and_delete_testimonial(
        HttpClientInterface $admin_http,
        string $customer_id,
    ): void {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/testimonials.php', ['action' => 'new']);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->post_admin_form($admin_http, '/admin/testimonials.php', ['action' => 'add_new'], [
            'formid' => $formid,
            'customers_id' => $customer_id,
            'customer_name' => self::TESTIMONIAL_NICK,
            'testimonials_text' => self::TESTIMONIAL_TEXT,
        ]);

        $list_html = $this->assert_admin_list_contains($admin_http, '/admin/testimonials.php', [], self::TESTIMONIAL_NICK);
        $testimonial_id = $this->parse_entity_id_near_needle($list_html, self::TESTIMONIAL_NICK, 'tID');

        $this->confirm_admin_delete($admin_http, '/admin/testimonials.php', 'tID', $testimonial_id);
        $this->assert_admin_list_not_contains($admin_http, '/admin/testimonials.php', [], self::TESTIMONIAL_NICK);
    }

    private function insert_and_delete_info_page(HttpClientInterface $admin_http): void {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/info_pages.php', ['action' => 'new']);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);
        $language_ids = $this->parse_bracket_language_ids($new_html, 'page_title');

        $body = [
            'formid' => $formid,
            'page_status' => '0',
            'slug' => self::INFO_PAGE_SLUG,
            'sort_order' => '99',
        ];
        foreach ($language_ids as $language_id) {
            $body["navbar_title[{$language_id}]"] = self::INFO_PAGE_TITLE;
            $body["page_title[{$language_id}]"] = self::INFO_PAGE_TITLE;
            $body["page_text[{$language_id}]"] = 'Installer acceptance info page body.';
        }

        $insert = $admin_http->request('POST', '/admin/info_pages.php', [
            'query' => ['action' => 'add_new'],
            'body' => $body,
        ]);
        $this->assertContains($insert->getStatusCode(), [200, 302]);
        $page_id = $this->parse_id_from_redirect_url((string) ($insert->getInfo('url') ?? ''), 'pID');
        if ($page_id === '') {
            $list_html = $this->assert_admin_list_contains($admin_http, '/admin/info_pages.php', [], self::INFO_PAGE_TITLE);
            $page_id = $this->parse_entity_id_near_needle($list_html, self::INFO_PAGE_TITLE, 'pID');
        }

        $this->assertNotSame('', $page_id);

        $this->confirm_admin_delete($admin_http, '/admin/info_pages.php', 'pID', $page_id);
        $this->assert_admin_list_not_contains($admin_http, '/admin/info_pages.php', [], self::INFO_PAGE_SLUG);
    }

    private function insert_and_delete_advert(HttpClientInterface $admin_http): void {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/advert_manager.php', ['action' => 'new']);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);
        $language_ids = $this->parse_bracket_language_ids($new_html, 'advert_html_text');

        $body = [
            'formid' => $formid,
            'advert_title' => self::ADVERT_TITLE,
            'advert_url' => '',
            'advert_fragment' => '',
            'sort_order' => '99',
            'advert_group' => 'Our Farm',
            'new_advert_group' => '',
            'advert_image_local' => '',
            'advert_image_target' => '',
        ];
        foreach ($language_ids as $language_id) {
            $body["advert_html_text[{$language_id}]"] = '<p>Installer advert HTML.</p>';
        }

        $this->post_admin_form($admin_http, '/admin/advert_manager.php', ['action' => 'add_new'], $body);

        $list_html = $this->assert_admin_list_contains($admin_http, '/admin/advert_manager.php', [], self::ADVERT_TITLE);
        $advert_id = $this->parse_entity_id_near_needle($list_html, self::ADVERT_TITLE, 'aID');

        $this->confirm_admin_delete($admin_http, '/admin/advert_manager.php', 'aID', $advert_id);
        $this->assert_admin_list_not_contains($admin_http, '/admin/advert_manager.php', [], self::ADVERT_TITLE);
    }

    private function insert_and_delete_outgoing_template(HttpClientInterface $admin_http): void {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/outgoing_tpl.php', ['action' => 'new']);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);
        $slug = $this->parse_first_select_option_value($new_html, 'slug');
        $this->assertNotSame('', $slug);
        $language_ids = $this->parse_bracket_language_ids($new_html, 'title');

        $body = [
            'formid' => $formid,
            'slug' => $slug,
        ];
        foreach ($language_ids as $language_id) {
            $body["title[{$language_id}]"] = self::OUTGOING_TITLE;
            $body["text[{$language_id}]"] = 'Installer outgoing template body.';
        }

        $insert = $admin_http->request('POST', '/admin/outgoing_tpl.php', [
            'query' => ['action' => 'insert'],
            'body' => $body,
        ]);
        $this->assertContains($insert->getStatusCode(), [200, 302]);
        $template_id = $this->parse_id_from_redirect_url((string) ($insert->getInfo('url') ?? ''), 'oID');
        if ($template_id === '') {
            $list_html = $this->assert_admin_list_contains($admin_http, '/admin/outgoing_tpl.php', [], $slug);
            $template_id = $this->parse_entity_id_near_needle($list_html, $slug, 'oID');
        }

        $this->assertNotSame('', $template_id);
        $this->assert_admin_list_contains($admin_http, '/admin/outgoing_tpl.php', [], $slug);

        $this->confirm_admin_delete(
            $admin_http,
            '/admin/outgoing_tpl.php',
            'oID',
            $template_id,
            'delete_confirm',
            ['slug' => $slug],
        );
        $after = $this->fetch_admin_page($admin_http, '/admin/outgoing_tpl.php');
        $this->assertStringNotContainsString(
            'oID=' . $template_id,
            $this->admin_list_table_body($after),
        );
    }

    private function register_storefront_customer(HttpClientInterface $shop_http): void {
        $shop_http->request('GET', '/');

        $create_account_page = $shop_http->request('GET', '/create_account.php');
        $this->assertSame(200, $create_account_page->getStatusCode());
        $create_html = $create_account_page->getContent(false);
        $formid = self::parse_hidden_input($create_html, 'formid');
        $this->assertNotSame('', $formid);

        $registered = $shop_http->request('POST', '/create_account.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'firstname' => self::CUSTOMER_FIRSTNAME,
                'lastname' => self::CUSTOMER_LASTNAME,
                'email_address' => self::CUSTOMER_EMAIL,
                'password' => self::CUSTOMER_PASSWORD,
                'street_address' => '1 Test Street',
                'city' => 'Testville',
                'postcode' => '90210',
                'country_id' => '223',
                'state' => 'Florida',
                'telephone' => '555-0100',
                'matc' => '1',
            ],
        ]);
        $this->assertContains($registered->getStatusCode(), [200, 302]);
    }

    private function parse_customer_id_from_admin(): string {
        $admin_http = $this->login_installed_admin();
        $customers_page = $admin_http->request('GET', '/admin/customers.php', [
            'query' => ['search' => self::CUSTOMER_EMAIL],
        ]);
        $this->assertSame(200, $customers_page->getStatusCode());
        $html = $customers_page->getContent(false);
        $this->assertStringContainsString(self::CUSTOMER_EMAIL, $html);

        if (preg_match('/[?&]cID=(\d+)/', $html, $matches) === 1) {
            return $matches[1];
        }

        $this->fail('customers.php did not expose cID for the content test customer');
    }

    private function parse_first_select_option_value(string $html, string $name): string {
        $pattern = '/name="' . preg_quote($name, '/') . '"[^>]*>(.*?)<\/select>/s';
        if (preg_match($pattern, $html, $select_match) !== 1) {
            return '';
        }

        if (preg_match_all('/<option[^>]*value="([^"]*)"/', $select_match[1], $options) === false) {
            return '';
        }

        foreach ($options[1] as $value) {
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
