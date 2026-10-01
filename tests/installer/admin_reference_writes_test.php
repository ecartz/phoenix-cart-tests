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
final class admin_reference_writes_test extends install_test_case
{
    use installer_admin_writes;

    private const COUNTRY_NAME = 'Phoenix Installer Country';

    private const LANGUAGE_NAME = 'Phoenix Installer Lang';

    private const LANGUAGE_CODE = 'qi';

    private const ZONE_NAME = 'Phoenix Installer Zone';

    private const TAX_CLASS_TITLE = 'Phoenix Installer Tax Class';

    private const TAX_RATE_DESCRIPTION = 'Phoenix Installer Tax Rate';

    private const GEO_ZONE_NAME = 'Phoenix Installer Geo Zone';

    private const CURRENCY_TITLE = 'Phoenix Installer Currency';

    private const CURRENCY_CODE = 'XIZ';

    private const MANUFACTURER_NAME = 'Phoenix Installer Manufacturer';

    private const ORDER_STATUS_NAME = 'Phoenix Installer Status';

    private const US_COUNTRY_ID = '223';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_admin_reference_entities_insert_and_delete(): void
    {
        $admin_http = $this->login_installed_admin();

        $this->insert_and_delete_country($admin_http);
        $this->insert_and_delete_language($admin_http);
        $this->insert_and_delete_zone($admin_http);
        $this->insert_and_delete_tax_class($admin_http);
        $geo_zone_id = $this->insert_geo_zone($admin_http);
        $this->insert_and_delete_tax_rate($admin_http, $geo_zone_id);
        $this->delete_geo_zone($admin_http, $geo_zone_id);
        $this->insert_and_delete_currency($admin_http);
        $this->insert_and_delete_manufacturer($admin_http);
        $this->insert_and_delete_order_status($admin_http);
    }

    private function insert_and_delete_country(HttpClientInterface $admin_http): void
    {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/countries.php', ['action' => 'new']);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->post_admin_form($admin_http, '/admin/countries.php', ['action' => 'insert'], [
            'formid' => $formid,
            'countries_name' => self::COUNTRY_NAME,
            'countries_iso_code_2' => 'QZ',
            'countries_iso_code_3' => 'QZX',
            'address_format_id' => '1',
        ]);

        $list_html = $this->assert_admin_list_contains(
            $admin_http,
            '/admin/countries.php',
            ['search' => self::COUNTRY_NAME],
            self::COUNTRY_NAME,
        );
        $country_id = $this->parse_entity_id_near_needle($list_html, self::COUNTRY_NAME, 'cID');

        $this->confirm_admin_delete($admin_http, '/admin/countries.php', 'cID', $country_id);
        $this->assert_admin_list_not_contains(
            $admin_http,
            '/admin/countries.php',
            ['search' => self::COUNTRY_NAME],
            self::COUNTRY_NAME,
        );
    }

    private function insert_and_delete_language(HttpClientInterface $admin_http): void
    {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/languages.php', ['action' => 'new']);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->post_admin_form($admin_http, '/admin/languages.php', ['action' => 'insert'], [
            'formid' => $formid,
            'name' => self::LANGUAGE_NAME,
            'code' => self::LANGUAGE_CODE,
            'image' => 'icon.gif',
            'directory' => 'english',
            'sort_order' => '99',
        ]);

        $list_html = $this->assert_admin_list_contains($admin_http, '/admin/languages.php', [], self::LANGUAGE_NAME);
        $language_id = $this->parse_entity_id_near_needle($list_html, self::LANGUAGE_NAME, 'lID');

        $this->confirm_admin_delete($admin_http, '/admin/languages.php', 'lID', $language_id);
        $this->assert_admin_list_not_contains($admin_http, '/admin/languages.php', [], self::LANGUAGE_NAME);
    }

    private function insert_and_delete_zone(HttpClientInterface $admin_http): void
    {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/zones.php', ['action' => 'new']);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->post_admin_form($admin_http, '/admin/zones.php', ['action' => 'insert'], [
            'formid' => $formid,
            'zone_name' => self::ZONE_NAME,
            'zone_code' => 'PIZ',
            'zone_country_id' => self::US_COUNTRY_ID,
        ]);

        $list_html = $this->assert_admin_list_contains($admin_http, '/admin/zones.php', [], self::ZONE_NAME);
        $zone_id = $this->parse_entity_id_near_needle($list_html, self::ZONE_NAME, 'cID');

        $this->confirm_admin_delete($admin_http, '/admin/zones.php', 'cID', $zone_id);
        $this->assert_admin_list_not_contains($admin_http, '/admin/zones.php', [], self::ZONE_NAME);
    }

    private function insert_and_delete_tax_class(HttpClientInterface $admin_http): void
    {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/tax_classes.php', ['action' => 'new']);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->post_admin_form($admin_http, '/admin/tax_classes.php', ['action' => 'insert'], [
            'formid' => $formid,
            'tax_class_title' => self::TAX_CLASS_TITLE,
            'tax_class_description' => 'Installer acceptance tax class.',
        ]);

        $list_html = $this->assert_admin_list_contains($admin_http, '/admin/tax_classes.php', [], self::TAX_CLASS_TITLE);
        $tax_class_id = $this->parse_entity_id_near_needle($list_html, self::TAX_CLASS_TITLE, 'tID');

        $this->confirm_admin_delete($admin_http, '/admin/tax_classes.php', 'tID', $tax_class_id);
        $this->assert_admin_list_not_contains($admin_http, '/admin/tax_classes.php', [], self::TAX_CLASS_TITLE);
    }

    private function insert_geo_zone(HttpClientInterface $admin_http): string
    {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/geo_zones.php', ['action' => 'new_zone']);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->post_admin_form($admin_http, '/admin/geo_zones.php', ['action' => 'insert_zone'], [
            'formid' => $formid,
            'geo_zone_name' => self::GEO_ZONE_NAME,
            'geo_zone_description' => 'Installer acceptance geo zone.',
        ]);

        $list_html = $this->assert_admin_list_contains($admin_http, '/admin/geo_zones.php', [], self::GEO_ZONE_NAME);

        return $this->parse_entity_id_near_needle($list_html, self::GEO_ZONE_NAME, 'zID');
    }

    private function insert_and_delete_tax_rate(HttpClientInterface $admin_http, string $geo_zone_id): void
    {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/tax_rates.php', ['action' => 'new']);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->post_admin_form($admin_http, '/admin/tax_rates.php', ['action' => 'insert'], [
            'formid' => $formid,
            'tax_class_id' => '1',
            'tax_zone_id' => $geo_zone_id,
            'tax_rate' => '5.0000',
            'tax_description' => self::TAX_RATE_DESCRIPTION,
            'tax_priority' => '1',
        ]);

        $list_html = $this->assert_admin_list_contains($admin_http, '/admin/tax_rates.php', [], self::TAX_RATE_DESCRIPTION);
        $tax_rate_id = $this->parse_entity_id_near_needle($list_html, self::TAX_RATE_DESCRIPTION, 'tID');

        $this->confirm_admin_delete($admin_http, '/admin/tax_rates.php', 'tID', $tax_rate_id);
        $this->assert_admin_list_not_contains($admin_http, '/admin/tax_rates.php', [], self::TAX_RATE_DESCRIPTION);
    }

    private function delete_geo_zone(HttpClientInterface $admin_http, string $geo_zone_id): void
    {
        $this->confirm_admin_delete(
            $admin_http,
            '/admin/geo_zones.php',
            'zID',
            $geo_zone_id,
            'delete_confirm_zone',
            [],
            [],
            'delete_zone',
        );
        $this->assert_admin_list_not_contains($admin_http, '/admin/geo_zones.php', [], self::GEO_ZONE_NAME);
    }

    private function insert_and_delete_currency(HttpClientInterface $admin_http): void
    {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/currencies.php', ['action' => 'new']);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->post_admin_form($admin_http, '/admin/currencies.php', ['action' => 'insert'], [
            'formid' => $formid,
            'title' => self::CURRENCY_TITLE,
            'code' => self::CURRENCY_CODE,
            'symbol_left' => '',
            'symbol_right' => '¤',
            'decimal_point' => '.',
            'thousands_point' => ',',
            'decimal_places' => '2',
            'value' => '1.0000',
        ]);

        $list_html = $this->assert_admin_list_contains($admin_http, '/admin/currencies.php', [], self::CURRENCY_TITLE);
        $currency_id = $this->parse_entity_id_near_needle($list_html, self::CURRENCY_TITLE, 'cID');

        $this->confirm_admin_delete($admin_http, '/admin/currencies.php', 'cID', $currency_id);
        $this->assert_admin_list_not_contains($admin_http, '/admin/currencies.php', [], self::CURRENCY_TITLE);
    }

    private function insert_and_delete_manufacturer(HttpClientInterface $admin_http): void
    {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/manufacturers.php', ['action' => 'new']);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);
        $language_ids = $this->parse_bracket_language_ids($new_html, 'manufacturers_url');

        $body = [
            'formid' => $formid,
            'manufacturers_name' => self::MANUFACTURER_NAME,
            'manufacturers_address' => '1 Installer Way',
            'manufacturers_email' => 'manufacturer@example.com',
        ];
        foreach ($language_ids as $language_id) {
            $body["manufacturers_url[{$language_id}]"] = '';
            $body["manufacturers_description[{$language_id}]"] = '';
            $body["manufacturers_seo_description[{$language_id}]"] = '';
            $body["manufacturers_seo_title[{$language_id}]"] = '';
        }

        $this->post_admin_form($admin_http, '/admin/manufacturers.php', ['action' => 'insert'], $body);

        $list_html = $this->assert_admin_list_contains($admin_http, '/admin/manufacturers.php', [], self::MANUFACTURER_NAME);
        $manufacturer_id = $this->parse_entity_id_near_needle($list_html, self::MANUFACTURER_NAME, 'mID');

        $this->confirm_admin_delete($admin_http, '/admin/manufacturers.php', 'mID', $manufacturer_id);
        $this->assert_admin_list_not_contains($admin_http, '/admin/manufacturers.php', [], self::MANUFACTURER_NAME);
    }

    private function insert_and_delete_order_status(HttpClientInterface $admin_http): void
    {
        $new_html = $this->fetch_admin_page($admin_http, '/admin/orders_status.php', ['action' => 'new']);
        $formid = self::parse_hidden_input($new_html, 'formid');
        $this->assertNotSame('', $formid);
        $language_ids = $this->parse_bracket_language_ids($new_html, 'orders_status_name');

        $body = ['formid' => $formid];
        foreach ($language_ids as $language_id) {
            $body["orders_status_name[{$language_id}]"] = self::ORDER_STATUS_NAME;
        }

        $this->post_admin_form($admin_http, '/admin/orders_status.php', ['action' => 'insert'], $body);

        $list_html = $this->assert_admin_list_contains($admin_http, '/admin/orders_status.php', [], self::ORDER_STATUS_NAME);
        $status_id = $this->parse_entity_id_near_needle($list_html, self::ORDER_STATUS_NAME, 'oID');

        $this->confirm_admin_delete($admin_http, '/admin/orders_status.php', 'oID', $status_id);
        $this->assert_admin_list_not_contains($admin_http, '/admin/orders_status.php', [], self::ORDER_STATUS_NAME);
    }
}
