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
final class admin_catalog_writes_test extends install_test_case
{
    use installer_admin_writes;

    private const ROOT_CATEGORY_PATH = '1';

    private const CATEGORY_NAME = 'Phoenix Installer Category';

    private const PRODUCT_NAME = 'Phoenix Installer Catalog Product';

    private const PRODUCT_NAME_UPDATED = 'Phoenix Installer Catalog Product Updated';

    private const PRODUCT_DESCRIPTION = 'Installer acceptance catalog write test product.';

    private const PRODUCT_MODEL = 'INSTALL-CAT';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_catalog_category_product_copy_and_move(): void
    {
        $admin_http = $this->login_installed_admin();

        $category_id = $this->insert_category($admin_http);
        $child_path = self::ROOT_CATEGORY_PATH . '_' . $category_id;

        $product_id = $this->insert_product($admin_http, self::ROOT_CATEGORY_PATH, self::PRODUCT_NAME);
        $this->update_product_name(
            $admin_http,
            self::ROOT_CATEGORY_PATH,
            $product_id,
            self::PRODUCT_NAME_UPDATED,
        );

        $copy_id = $this->duplicate_product_to_category(
            $admin_http,
            self::ROOT_CATEGORY_PATH,
            $product_id,
            $category_id,
            self::PRODUCT_NAME_UPDATED,
        );
        $this->delete_product($admin_http, $child_path, $copy_id, $category_id);

        $this->move_product($admin_http, self::ROOT_CATEGORY_PATH, $product_id, $category_id);
        $this->move_product($admin_http, $child_path, $product_id, (int) self::ROOT_CATEGORY_PATH);

        $this->delete_product(
            $admin_http,
            self::ROOT_CATEGORY_PATH,
            $product_id,
            (int) self::ROOT_CATEGORY_PATH,
        );
        $this->delete_category($admin_http, $child_path, $category_id);
    }

    private function insert_category(HttpClientInterface $admin_http): string
    {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/catalog.php', [
            'cPath' => self::ROOT_CATEGORY_PATH,
            'action' => 'new_category',
        ]);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);
        $language_ids = $this->parse_bracket_language_ids($new_html, 'categories_name');

        $body = [
            'formid' => $formid,
            'sort_order' => '99',
        ];
        foreach ($language_ids as $language_id) {
            $body["categories_name[{$language_id}]"] = self::CATEGORY_NAME;
            $body["categories_description[{$language_id}]"] = '';
            $body["categories_seo_description[{$language_id}]"] = '';
            $body["categories_seo_title[{$language_id}]"] = '';
        }

        $insert = $admin_http->request('POST', '/admin/catalog.php', [
            'query' => [
                'cPath' => self::ROOT_CATEGORY_PATH,
                'action' => 'insert_category',
            ],
            'body' => $body,
        ]);
        $this->assertContains($insert->getStatusCode(), [200, 302]);
        $insert_url = (string) ($insert->getInfo('url') ?? '');
        $category_id = $this->parse_id_from_redirect_url($insert_url, 'cID');
        if ($category_id === '') {
            $list_html = $this->assert_admin_list_contains(
                $admin_http,
                '/admin/catalog.php',
                ['cPath' => self::ROOT_CATEGORY_PATH],
                self::CATEGORY_NAME,
            );
            $category_id = $this->parse_entity_id_near_needle($list_html, self::CATEGORY_NAME, 'cID');
        }

        $this->assertNotSame('', $category_id);
        $this->assert_admin_list_contains(
            $admin_http,
            '/admin/catalog.php',
            ['cPath' => self::ROOT_CATEGORY_PATH],
            self::CATEGORY_NAME,
        );

        return $category_id;
    }

    private function insert_product(
        HttpClientInterface $admin_http,
        string $category_path,
        string $product_name,
    ): string {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/catalog.php', [
            'cPath' => $category_path,
            'action' => 'new_product',
        ]);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);
        $language_id = $this->parse_products_name_language_id($new_html);
        $products_date_added = self::parse_hidden_input($new_html, 'products_date_added');
        $this->assertNotSame('', $products_date_added);

        $this->post_admin_form($admin_http, '/admin/catalog.php', [
            'cPath' => $category_path,
            'action' => 'insert_product',
        ], $this->product_post_body(
            $formid,
            $language_id,
            $products_date_added,
            $product_name,
        ));

        $list_html = $this->assert_admin_list_contains(
            $admin_http,
            '/admin/catalog.php',
            ['cPath' => $category_path],
            $product_name,
        );

        return $this->parse_entity_id_near_needle($list_html, $product_name, 'pID');
    }

    private function update_product_name(
        HttpClientInterface $admin_http,
        string $category_path,
        string $product_id,
        string $updated_name,
    ): void {
        $edit_html = $this->fetch_admin_page($admin_http, '/admin/catalog.php', [
            'cPath' => $category_path,
            'pID' => $product_id,
            'action' => 'new_product',
        ]);
        $formid = self::parse_hidden_input($edit_html, 'formid');
        $this->assertNotSame('', $formid);
        $language_id = $this->parse_products_name_language_id($edit_html);
        $products_date_added = self::parse_hidden_input($edit_html, 'products_date_added');
        $this->assertNotSame('', $products_date_added);

        $this->post_admin_form($admin_http, '/admin/catalog.php', [
            'cPath' => $category_path,
            'pID' => $product_id,
            'action' => 'update_product',
        ], $this->product_post_body(
            $formid,
            $language_id,
            $products_date_added,
            $updated_name,
        ));

        $this->assert_admin_list_contains(
            $admin_http,
            '/admin/catalog.php',
            ['cPath' => $category_path],
            $updated_name,
        );
    }

    private function duplicate_product_to_category(
        HttpClientInterface $admin_http,
        string $source_path,
        string $product_id,
        string $target_category_id,
        string $product_name,
    ): string {
        $copy_html = $this->fetch_admin_page($admin_http, '/admin/catalog.php', [
            'cPath' => $source_path,
            'pID' => $product_id,
            'action' => 'copy_to',
        ]);
        $formid = self::parse_hidden_input($copy_html, 'formid');
        $this->assertNotSame('', $formid);

        $copy = $admin_http->request('POST', '/admin/catalog.php', [
            'query' => [
                'cPath' => $source_path,
                'action' => 'copy_to_confirm',
            ],
            'body' => [
                'formid' => $formid,
                'products_id' => $product_id,
                'categories_id' => $target_category_id,
                'copy_as' => 'duplicate',
            ],
        ]);
        $this->assertContains($copy->getStatusCode(), [200, 302]);
        $copy_url = (string) ($copy->getInfo('url') ?? '');
        $copy_id = $this->parse_id_from_redirect_url($copy_url, 'pID');

        $target_path = self::ROOT_CATEGORY_PATH . '_' . $target_category_id;
        if ($copy_id === '') {
            $list_html = $this->assert_admin_list_contains(
                $admin_http,
                '/admin/catalog.php',
                ['cPath' => $target_path],
                $product_name,
            );
            $copy_id = $this->parse_entity_id_near_needle($list_html, $product_name, 'pID');
        }

        $this->assertNotSame('', $copy_id);
        $this->assertNotSame($product_id, $copy_id);

        return $copy_id;
    }

    private function move_product(
        HttpClientInterface $admin_http,
        string $category_path,
        string $product_id,
        int $target_category_id,
    ): void {
        $move_html = $this->fetch_admin_page($admin_http, '/admin/catalog.php', [
            'cPath' => $category_path,
            'pID' => $product_id,
            'action' => 'move_product',
        ]);
        $formid = self::parse_hidden_input($move_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->post_admin_form($admin_http, '/admin/catalog.php', [
            'cPath' => $category_path,
            'action' => 'move_product_confirm',
        ], [
            'formid' => $formid,
            'products_id' => $product_id,
            'move_to_category_id' => (string) $target_category_id,
        ]);
    }

    private function delete_product(
        HttpClientInterface $admin_http,
        string $category_path,
        string $product_id,
        int $category_id_for_confirm,
    ): void {
        $delete_html = $this->fetch_admin_page($admin_http, '/admin/catalog.php', [
            'cPath' => $category_path,
            'pID' => $product_id,
            'action' => 'delete_product',
        ]);
        $formid = self::parse_hidden_input($delete_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->post_admin_form($admin_http, '/admin/catalog.php', [
            'cPath' => $category_path,
            'action' => 'delete_product_confirm',
        ], [
            'formid' => $formid,
            'products_id' => $product_id,
            'product_categories[]' => (string) $category_id_for_confirm,
        ]);
    }

    private function delete_category(
        HttpClientInterface $admin_http,
        string $category_path,
        string $category_id,
    ): void {
        $delete_html = $this->fetch_admin_page($admin_http, '/admin/catalog.php', [
            'cPath' => $category_path,
            'cID' => $category_id,
            'action' => 'delete_category',
        ]);
        $formid = self::parse_hidden_input($delete_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->post_admin_form($admin_http, '/admin/catalog.php', [
            'cPath' => $category_path,
            'action' => 'delete_category_confirm',
        ], [
            'formid' => $formid,
            'categories_id' => $category_id,
        ]);

        $this->assert_admin_list_not_contains(
            $admin_http,
            '/admin/catalog.php',
            ['cPath' => self::ROOT_CATEGORY_PATH],
            self::CATEGORY_NAME,
        );
    }

    /**
     * @return array<string, string>
     */
    private function product_post_body(
        string $formid,
        string $language_id,
        string $products_date_added,
        string $product_name,
    ): array {
        return [
            'formid' => $formid,
            'products_date_added' => $products_date_added,
            'products_status' => '0',
            'products_quantity' => '1',
            'products_date_available' => '',
            'manufacturers_id' => '',
            'importers_id' => '',
            'products_model' => self::PRODUCT_MODEL,
            'products_tax_class_id' => '1',
            'products_price' => '1.00',
            'products_price_gross' => '1.00',
            'products_weight' => '0.5',
            'products_gtin' => '',
            "products_name[{$language_id}]" => $product_name,
            "products_description[{$language_id}]" => self::PRODUCT_DESCRIPTION,
            "products_url[{$language_id}]" => '',
            "products_seo_title[{$language_id}]" => '',
            "products_seo_description[{$language_id}]" => '',
            "products_seo_keywords[{$language_id}]" => '',
        ];
    }

    private function parse_products_name_language_id(string $html): string
    {
        if (preg_match('/name="products_name\[(\d+)\]"/', $html, $matches) === 1) {
            return $matches[1];
        }

        return '1';
    }
}
