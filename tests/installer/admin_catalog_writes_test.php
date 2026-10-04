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
final class admin_catalog_writes_test extends install_test_case {

    use installer_admin_writes;

    private const ROOT_CATEGORY_PATH = '1';

    private const PRODUCT_CATEGORY_PATH = '1_3';

    private const CATEGORY_NAME = 'Phoenix Installer Category';

    private const PRODUCT_NAME = 'Phoenix Installer Catalog Product';

    private const PRODUCT_NAME_UPDATED = 'Phoenix Installer Catalog Product Updated';

    private const PRODUCT_DESCRIPTION = 'Installer acceptance catalog write test product.';

    private const PRODUCT_MODEL = 'INSTALL-CAT';

    private const STOREFRONT_PRODUCT_NAME = 'Phoenix Installer Storefront Product';

    private const STOREFRONT_PRODUCT_MODEL = 'INSTALL-SF';

    private const STOREFRONT_DESCRIPTION_INITIAL = 'Installer storefront description before admin save.';

    private const STOREFRONT_DESCRIPTION = 'Installer storefront description after admin save.';

    private const STOREFRONT_PRICE = '6.41';

    private const STOREFRONT_IMAGE_NAME = 'phoenix-installer-catalog-product.png';

    private const IMAGE_UPLOAD_BLOCKED = 'Product image upload is blocked: the admin product form is not a normal multipart post the HTTP client can send.';

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_catalog_category_product_copy_and_move(): void {
        $admin_http = $this->login_installed_admin();

        $category_id = $this->insert_category($admin_http);
        $child_path = self::ROOT_CATEGORY_PATH . '_' . $category_id;

        $product_id = $this->insert_product($admin_http, self::PRODUCT_CATEGORY_PATH, self::PRODUCT_NAME);
        $this->update_product_name(
            $admin_http,
            self::PRODUCT_CATEGORY_PATH,
            $product_id,
            self::PRODUCT_NAME_UPDATED,
        );

        $copy_id = $this->duplicate_product_to_category(
            $admin_http,
            self::PRODUCT_CATEGORY_PATH,
            $product_id,
            $category_id,
            self::PRODUCT_NAME_UPDATED,
        );
        $this->delete_product($admin_http, $child_path, $copy_id, (int) $category_id);

        $this->move_product($admin_http, self::PRODUCT_CATEGORY_PATH, $product_id, (int) $category_id);
        $this->move_product($admin_http, $child_path, $product_id, (int) self::ROOT_CATEGORY_PATH);

        $this->delete_product(
            $admin_http,
            self::PRODUCT_CATEGORY_PATH,
            $product_id,
            (int) self::ROOT_CATEGORY_PATH,
        );
        $this->delete_category($admin_http, $child_path, $category_id);
    }

    public function test_admin_product_price_and_description_reach_storefront(): void {
        $admin_http = $this->login_installed_admin();
        $product_id = $this->insert_product(
            $admin_http,
            self::PRODUCT_CATEGORY_PATH,
            self::STOREFRONT_PRODUCT_NAME,
            '1',
            '1.00',
            self::STOREFRONT_DESCRIPTION_INITIAL,
            self::STOREFRONT_PRODUCT_MODEL,
        );

        $this->update_product(
            $admin_http,
            self::PRODUCT_CATEGORY_PATH,
            $product_id,
            self::STOREFRONT_PRODUCT_NAME,
            '1',
            self::STOREFRONT_PRICE,
            self::STOREFRONT_DESCRIPTION,
            self::STOREFRONT_PRODUCT_MODEL,
        );

        $storefront_html = $this->fetch_storefront_product($product_id);
        $this->assertStringContainsString(self::STOREFRONT_DESCRIPTION, $storefront_html);
        $this->assertStringNotContainsString(self::STOREFRONT_DESCRIPTION_INITIAL, $storefront_html);
        $this->assertMatchesRegularExpression(
            '/<span class="productPrice">\$' . preg_quote(self::STOREFRONT_PRICE, '/') . '<\/span>/',
            $storefront_html,
        );

        $this->delete_product(
            $admin_http,
            self::PRODUCT_CATEGORY_PATH,
            $product_id,
            3,
        );
    }

    public function test_admin_product_image_upload_reaches_storefront(): void {
        $admin_http = $this->login_installed_admin();
        $new_html = $this->fetch_admin_page($admin_http, '/admin/catalog.php', [
            'cPath' => self::PRODUCT_CATEGORY_PATH,
            'action' => 'new_product',
        ]);
        if (!$this->catalog_product_form_accepts_multipart_image($new_html)) {
            $this->markTestSkipped(self::IMAGE_UPLOAD_BLOCKED);
        }

        $image_path = $this->storefront_image_fixture_path();
        $product_id = $this->insert_product(
            $admin_http,
            self::PRODUCT_CATEGORY_PATH,
            self::STOREFRONT_PRODUCT_NAME,
            '1',
            self::STOREFRONT_PRICE,
            self::STOREFRONT_DESCRIPTION,
            self::STOREFRONT_PRODUCT_MODEL,
        );

        $this->update_product(
            $admin_http,
            self::PRODUCT_CATEGORY_PATH,
            $product_id,
            self::STOREFRONT_PRODUCT_NAME,
            '1',
            self::STOREFRONT_PRICE,
            self::STOREFRONT_DESCRIPTION,
            self::STOREFRONT_PRODUCT_MODEL,
            $image_path,
        );

        $storefront_html = $this->fetch_storefront_product($product_id);
        $this->assertStringContainsString('images/' . self::STOREFRONT_IMAGE_NAME, $storefront_html);
        $this->assertStringContainsString(self::STOREFRONT_DESCRIPTION, $storefront_html);
        $this->assertMatchesRegularExpression(
            '/<span class="productPrice">\$' . preg_quote(self::STOREFRONT_PRICE, '/') . '<\/span>/',
            $storefront_html,
        );
        $saved_image = installer_bootstrap::catalog_copy_root()
            . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . self::STOREFRONT_IMAGE_NAME;
        $this->assertFileExists($saved_image);

        $this->delete_product(
            $admin_http,
            self::PRODUCT_CATEGORY_PATH,
            $product_id,
            3,
        );
        $this->assertFileDoesNotExist($saved_image);
        @unlink($image_path);
    }

    private function insert_category(HttpClientInterface $admin_http): string {
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
        string $products_status = '0',
        string $products_price = '1.00',
        string $products_description = self::PRODUCT_DESCRIPTION,
        string $products_model = self::PRODUCT_MODEL,
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

        $insert = $admin_http->request('POST', '/admin/catalog.php', [
            'query' => [
                'cPath' => $category_path,
                'action' => 'insert_product',
            ],
            'body' => $this->product_post_body(
                $formid,
                $language_id,
                $products_date_added,
                $product_name,
                $products_status,
                $products_price,
                $products_description,
                $products_model,
            ),
        ]);
        $this->assertContains($insert->getStatusCode(), [200, 302]);
        $insert_url = (string) ($insert->getInfo('url') ?? '');
        $product_id = $this->parse_id_from_redirect_url($insert_url, 'pID');

        $list_html = $this->assert_admin_list_contains(
            $admin_http,
            '/admin/catalog.php',
            ['cPath' => $category_path],
            $product_name,
        );

        if ($product_id === '') {
            $product_id = $this->parse_entity_id_near_needle($list_html, $product_name, 'pID');
        }

        $this->assertNotSame('', $product_id);

        return $product_id;
    }

    private function update_product_name(
        HttpClientInterface $admin_http,
        string $category_path,
        string $product_id,
        string $updated_name,
    ): void {
        $this->update_product($admin_http, $category_path, $product_id, $updated_name);
    }

    private function update_product(
        HttpClientInterface $admin_http,
        string $category_path,
        string $product_id,
        string $product_name,
        string $products_status = '0',
        string $products_price = '1.00',
        string $products_description = self::PRODUCT_DESCRIPTION,
        string $products_model = self::PRODUCT_MODEL,
        ?string $image_path = null,
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
        $body = $this->product_post_body(
            $formid,
            $language_id,
            $products_date_added,
            $product_name,
            $products_status,
            $products_price,
            $products_description,
            $products_model,
        );

        if ($image_path !== null) {
            if (!$this->catalog_product_form_accepts_multipart_image($edit_html)) {
                $this->markTestSkipped(self::IMAGE_UPLOAD_BLOCKED);
            }

            $this->post_admin_multipart($admin_http, '/admin/catalog.php', [
                'cPath' => $category_path,
                'pID' => $product_id,
                'action' => 'update_product',
            ], $body, [
                'products_image' => $image_path,
            ]);
        } else {
            $this->post_admin_form($admin_http, '/admin/catalog.php', [
                'cPath' => $category_path,
                'pID' => $product_id,
                'action' => 'update_product',
            ], $body);
        }

        $this->assert_admin_list_contains(
            $admin_http,
            '/admin/catalog.php',
            ['cPath' => $category_path],
            $product_name,
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
        $this->fetch_admin_page($admin_http, '/admin/catalog.php', [
            'cPath' => $category_path,
            'pID' => $product_id,
        ]);

        $delete_html = $this->fetch_admin_page($admin_http, '/admin/catalog.php', [
            'cPath' => $category_path,
            'pID' => $product_id,
            'action' => 'delete_product',
        ]);
        $formid = self::parse_formid_from_page($delete_html);
        if ($formid === '') {
            $formid = $this->resolve_admin_formid($admin_http);
        }
        $this->assertNotSame('', $formid);

        $this->post_admin_form($admin_http, '/admin/catalog.php', [
            'cPath' => $category_path,
            'action' => 'delete_product_confirm',
            'formid' => $formid,
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
        $this->fetch_admin_page($admin_http, '/admin/catalog.php', [
            'cPath' => $category_path,
            'cID' => $category_id,
        ]);

        $delete_html = $this->fetch_admin_page($admin_http, '/admin/catalog.php', [
            'cPath' => $category_path,
            'cID' => $category_id,
            'action' => 'delete_category',
        ]);
        $formid = self::parse_formid_from_page($delete_html);
        if ($formid === '') {
            $formid = $this->resolve_admin_formid($admin_http);
        }
        $this->assertNotSame('', $formid);

        $this->post_admin_form($admin_http, '/admin/catalog.php', [
            'cPath' => $category_path,
            'action' => 'delete_category_confirm',
            'formid' => $formid,
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
        string $products_status = '0',
        string $products_price = '1.00',
        string $products_description = self::PRODUCT_DESCRIPTION,
        string $products_model = self::PRODUCT_MODEL,
    ): array {
        return [
            'formid' => $formid,
            'products_date_added' => $products_date_added,
            'products_status' => $products_status,
            'products_quantity' => '1',
            'products_date_available' => '',
            'manufacturers_id' => '',
            'importers_id' => '',
            'products_model' => $products_model,
            'products_tax_class_id' => '1',
            'products_price' => $products_price,
            'products_price_gross' => $products_price,
            'products_weight' => '0.5',
            'products_gtin' => '',
            "products_name[{$language_id}]" => $product_name,
            "products_description[{$language_id}]" => $products_description,
            "products_url[{$language_id}]" => '',
            "products_seo_title[{$language_id}]" => '',
            "products_seo_description[{$language_id}]" => '',
            "products_seo_keywords[{$language_id}]" => '',
        ];
    }

    private function parse_products_name_language_id(string $html): string {
        if (preg_match('/name="products_name\[(\d+)\]"/', $html, $matches) === 1) {
            return $matches[1];
        }

        return '1';
    }

    private function fetch_storefront_product(string $product_id): string {
        $shop_http = installer_bootstrap::client();
        $product_page = $shop_http->request('GET', '/product_info.php', [
            'query' => ['products_id' => $product_id],
        ]);
        $this->assertSame(200, $product_page->getStatusCode());

        return $product_page->getContent(false);
    }

    private function catalog_product_form_accepts_multipart_image(string $html): bool {
        if (!str_contains($html, 'enctype="multipart/form-data"')) {
            return false;
        }

        return preg_match(
            '/<input\b(?=[^>]*\btype="file")(?=[^>]*\bname="products_image")[^>]*>/i',
            $html,
        ) === 1;
    }

    private function storefront_image_fixture_path(): string {
        $source = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'fixtures'
            . DIRECTORY_SEPARATOR . 'installer-store-logo-test.png';
        $this->assertFileExists($source);

        $directory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'working';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            $this->fail('Cannot create working directory for the product image fixture.');
        }

        $destination = $directory . DIRECTORY_SEPARATOR . self::STOREFRONT_IMAGE_NAME;
        if (!copy($source, $destination)) {
            $this->fail('Cannot copy the product image fixture.');
        }

        return $destination;
    }

}
