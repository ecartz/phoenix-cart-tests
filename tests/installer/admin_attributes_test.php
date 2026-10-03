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
final class admin_attributes_test extends install_test_case
{
    use installer_admin_writes;

    private const PEARS_PRODUCT_ID = '3';

    private const OPTION_NAME = 'Phoenix Installer Option';

    private const VALUE_NAME = 'Phoenix Installer Value';

    private const ATTRIBUTE_PRICE = '1.25';

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_product_attributes_link_and_cleanup(): void {
        $admin_http = $this->login_installed_admin();
        $page_html = $this->fetch_admin_page($admin_http, '/admin/products_attributes.php');

        $option_id = $this->parse_hidden_input_value($page_html, 'products_options_id');
        $this->assertNotSame('', $option_id);
        $value_id = $this->parse_hidden_input_value($page_html, 'value_id');
        $this->assertNotSame('', $value_id);

        $language_ids = $this->parse_bracket_language_ids($page_html, 'option_name');
        $this->add_product_option($admin_http, $page_html, $option_id, $language_ids);
        $this->assert_admin_list_contains(
            $admin_http,
            '/admin/products_attributes.php',
            [],
            self::OPTION_NAME,
        );

        $values_page = $this->fetch_admin_page($admin_http, '/admin/products_attributes.php');
        $this->add_product_option_value($admin_http, $values_page, $option_id, $value_id, $language_ids);
        $this->assert_admin_list_contains(
            $admin_http,
            '/admin/products_attributes.php',
            [],
            self::VALUE_NAME,
        );

        $attributes_page = $this->fetch_admin_page($admin_http, '/admin/products_attributes.php');
        $formid = self::parse_formid_for_admin_action($attributes_page, 'add_product_attributes');
        $this->assertNotSame('', $formid);

        $attribute_body = [
            'formid' => $formid,
            'products_id' => self::PEARS_PRODUCT_ID,
            'options_id' => $option_id,
            'values_id' => $value_id,
            'value_price' => self::ATTRIBUTE_PRICE,
            'price_prefix' => '+',
        ];
        if (str_contains($attributes_page, 'products_attributes_filename')) {
            $attribute_body['products_attributes_filename'] = '';
            $attribute_body['products_attributes_maxdays'] = '';
            $attribute_body['products_attributes_maxcount'] = '';
        }

        $this->post_admin_form($admin_http, '/admin/products_attributes.php', [
            'option_page' => '1',
            'value_page' => '1',
            'attribute_page' => '1',
            'action' => 'add_product_attributes',
            'formid' => $formid,
        ], $attribute_body);

        $linked_html = $this->assert_admin_list_contains(
            $admin_http,
            '/admin/products_attributes.php',
            [],
            'Pears',
        );
        $this->assertStringContainsString(self::OPTION_NAME, $linked_html);
        $this->assertStringContainsString(self::VALUE_NAME, $linked_html);
        $attribute_id = $this->parse_attribute_id_near_pears($linked_html);

        $this->delete_product_attribute($admin_http, $attribute_id);
        $this->delete_option_value($admin_http, $value_id);
        $this->delete_product_option($admin_http, $option_id);

        $final_html = $this->fetch_admin_page($admin_http, '/admin/products_attributes.php');
        $this->assertStringNotContainsString(self::OPTION_NAME, $final_html);
        $this->assertStringNotContainsString(self::VALUE_NAME, $final_html);
    }

    /**
     * @param list<string> $language_ids
     */
    private function add_product_option(
        HttpClientInterface $admin_http,
        string $page_html,
        string $option_id,
        array $language_ids,
    ): void {
        $formid = self::parse_formid_for_admin_action($page_html, 'add_product_options');
        $this->assertNotSame('', $formid);

        $body = [
            'formid' => $formid,
            'products_options_id' => $option_id,
        ];
        foreach ($language_ids as $language_id) {
            $body["option_name[{$language_id}]"] = self::OPTION_NAME;
            $body["sort_order[{$language_id}]"] = '99';
        }

        $this->post_admin_form($admin_http, '/admin/products_attributes.php', [
            'option_page' => '1',
            'value_page' => '1',
            'attribute_page' => '1',
            'action' => 'add_product_options',
            'formid' => $formid,
        ], $body);
    }

    /**
     * @param list<string> $language_ids
     */
    private function add_product_option_value(
        HttpClientInterface $admin_http,
        string $page_html,
        string $option_id,
        string $value_id,
        array $language_ids,
    ): void {
        $formid = self::parse_formid_for_admin_action($page_html, 'add_product_option_values');
        $this->assertNotSame('', $formid);

        $body = [
            'formid' => $formid,
            'option_id' => $option_id,
            'value_id' => $value_id,
        ];
        foreach ($language_ids as $language_id) {
            $body["value_name[{$language_id}]"] = self::VALUE_NAME;
            $body["sort_order[{$language_id}]"] = '99';
        }

        $this->post_admin_form($admin_http, '/admin/products_attributes.php', [
            'option_page' => '1',
            'value_page' => '1',
            'attribute_page' => '1',
            'action' => 'add_product_option_values',
            'formid' => $formid,
        ], $body);
    }

    private function delete_product_attribute(HttpClientInterface $admin_http, string $attribute_id): void {
        $page = $this->fetch_admin_page($admin_http, '/admin/products_attributes.php');
        $formid = self::parse_formid_from_page($page);
        $this->assertNotSame('', $formid);

        $this->fetch_admin_page($admin_http, '/admin/products_attributes.php', [
            'option_page' => '1',
            'value_page' => '1',
            'attribute_page' => '1',
            'formid' => $formid,
            'action' => 'delete_product_attribute',
            'attribute_id' => $attribute_id,
        ]);
        $this->fetch_admin_page($admin_http, '/admin/products_attributes.php', [
            'option_page' => '1',
            'value_page' => '1',
            'attribute_page' => '1',
            'formid' => $formid,
            'action' => 'delete_attribute',
            'attribute_id' => $attribute_id,
        ]);
    }

    private function delete_option_value(HttpClientInterface $admin_http, string $value_id): void {
        $page = $this->fetch_admin_page($admin_http, '/admin/products_attributes.php');
        $formid = self::parse_formid_from_page($page);
        $this->assertNotSame('', $formid);

        $this->fetch_admin_page($admin_http, '/admin/products_attributes.php', [
            'option_page' => '1',
            'value_page' => '1',
            'attribute_page' => '1',
            'formid' => $formid,
            'action' => 'delete_option_value',
            'value_id' => $value_id,
        ]);
        $this->fetch_admin_page($admin_http, '/admin/products_attributes.php', [
            'option_page' => '1',
            'value_page' => '1',
            'attribute_page' => '1',
            'formid' => $formid,
            'action' => 'delete_value',
            'value_id' => $value_id,
        ]);
    }

    private function delete_product_option(HttpClientInterface $admin_http, string $option_id): void {
        $page = $this->fetch_admin_page($admin_http, '/admin/products_attributes.php');
        $formid = self::parse_formid_from_page($page);
        $this->assertNotSame('', $formid);

        $this->fetch_admin_page($admin_http, '/admin/products_attributes.php', [
            'option_page' => '1',
            'value_page' => '1',
            'attribute_page' => '1',
            'formid' => $formid,
            'action' => 'delete_product_option',
            'option_id' => $option_id,
        ]);
        $this->fetch_admin_page($admin_http, '/admin/products_attributes.php', [
            'option_page' => '1',
            'value_page' => '1',
            'attribute_page' => '1',
            'formid' => $formid,
            'action' => 'delete_option',
            'option_id' => $option_id,
        ]);
    }

    private function parse_hidden_input_value(string $html, string $name): string {
        $pattern = '/name="' . preg_quote($name, '/') . '"[^>]*value="(\d+)"/';
        if (preg_match($pattern, $html, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/value="(\d+)"[^>]*name="' . preg_quote($name, '/') . '"/', $html, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    private function parse_attribute_id_near_pears(string $html): string {
        $offset = strpos($html, 'Pears');
        if ($offset === false) {
            $this->fail('products_attributes.php did not list Pears after linking an attribute');
        }

        $window = substr($html, max(0, $offset - 200), 1200);
        if (preg_match('/attribute_id=(\d+)/', $window, $matches) === 1) {
            return $matches[1];
        }

        $this->fail('products_attributes.php did not expose attribute_id for the Pears link');
    }
}
