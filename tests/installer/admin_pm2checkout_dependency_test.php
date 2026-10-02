<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\cookie_jar_http_client;
use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_admin_writes;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[Group('installer')]
final class admin_pm2checkout_dependency_test extends install_test_case
{
    use installer_admin_writes;

    private const CUSTOMER_DATA_SET = 'customer_data';

    private const PAYMENT_SET = 'payment';

    private const PM2CHECKOUT_CODE = 'pm2checkout';

    /** @var list<string> Mirrors pm2checkout::REQUIRES in PhoenixCart */
    private const PM2CHECKOUT_REQUIRED_ABILITIES = [
        'firstname',
        'lastname',
        'street_address',
        'city',
        'postcode',
        'country',
        'telephone',
        'email_address',
    ];

    /**
     * Customer-data module codes that provide pm2checkout::REQUIRES abilities.
     *
     * @var array<string, string> ability => module code
     */
    private const CUSTOMER_DATA_MODULE_FOR_ABILITY = [
        'firstname' => 'cd_firstname',
        'lastname' => 'cd_lastname',
        'street_address' => 'cd_street_address',
        'city' => 'cd_city',
        'postcode' => 'cd_postcode',
        'country' => 'cd_country',
        'telephone' => 'cd_telephone',
        'email_address' => 'cd_email_address',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
    }

    public function test_pm2checkout_install_fails_until_customer_data_dependencies_are_installed(): void
    {
        $admin_http = $this->login_installed_admin();

        if (!$this->pm2checkout_available_on_payment_new_list($admin_http)) {
            $this->markTestSkipped(self::PM2CHECKOUT_CODE . ' is not available on payment list=new.');
        }

        $missing_before = $this->missing_pm2checkout_abilities($admin_http);
        if ($missing_before === []) {
            $this->markTestSkipped('Sample shop already satisfies pm2checkout customer-data requirements.');
        }

        $this->assert_pm2checkout_install_rejected($admin_http);

        $this->install_missing_pm2checkout_customer_data_modules($admin_http);

        $list_set = $this->assert_pm2checkout_install_succeeds($admin_http);
        $this->assertSame(self::PAYMENT_SET, $list_set);

        $this->remove_installed_module($admin_http, $list_set, self::PM2CHECKOUT_CODE);
    }

    private function pm2checkout_available_on_payment_new_list(HttpClientInterface $admin_http): bool
    {
        $new_module_html = $this->fetch_admin_page($admin_http, '/admin/modules.php', [
            'set' => self::PAYMENT_SET,
            'list' => 'new',
            'module' => self::PM2CHECKOUT_CODE,
        ]);

        return self::parse_new_module_code_from_modules_html($new_module_html) === self::PM2CHECKOUT_CODE;
    }

    private function assert_pm2checkout_install_rejected(HttpClientInterface $admin_http): void
    {
        $install_formid = $this->pm2checkout_install_formid($admin_http);
        $install_response = $this->post_pm2checkout_install_without_following_redirects(
            $admin_http,
            $install_formid,
        );

        $location = $this->redirect_location($install_response);
        $this->assertFalse(
            $this->location_includes_module($location, self::PM2CHECKOUT_CODE),
            'Rejected install redirect should not target installed module detail: ' . $location,
        );

        $list_set = $this->parse_admin_modules_set_from_response($install_response) ?? self::PAYMENT_SET;
        $after_install_html = $this->fetch_admin_page($admin_http, '/admin/modules.php', ['set' => $list_set]);

        $this->assert_admin_module_list_not_contains($after_install_html, self::PM2CHECKOUT_CODE);
    }

    private function assert_pm2checkout_install_succeeds(HttpClientInterface $admin_http): string
    {
        $install_formid = $this->pm2checkout_install_formid($admin_http);
        $install_response = $this->post_pm2checkout_install_without_following_redirects(
            $admin_http,
            $install_formid,
        );

        $location = $this->redirect_location($install_response);
        $this->assertTrue(
            $this->location_includes_module($location, self::PM2CHECKOUT_CODE),
            'Successful install redirect should include module detail: ' . $location,
        );

        $list_set = $this->parse_admin_modules_set_from_response($install_response) ?? self::PAYMENT_SET;
        $installed_html = $this->fetch_admin_page($admin_http, '/admin/modules.php', ['set' => $list_set]);
        $this->assert_admin_module_list_contains($installed_html, self::PM2CHECKOUT_CODE);

        return $list_set;
    }

    private function pm2checkout_install_formid(HttpClientInterface $admin_http): string
    {
        $new_module_html = $this->fetch_admin_page($admin_http, '/admin/modules.php', [
            'set' => self::PAYMENT_SET,
            'list' => 'new',
            'module' => self::PM2CHECKOUT_CODE,
        ]);
        $install_formid = self::parse_hidden_input($new_module_html, 'formid');
        $this->assertNotSame('', $install_formid);

        return $install_formid;
    }

    private function post_pm2checkout_install_without_following_redirects(
        HttpClientInterface $admin_http,
        string $install_formid,
    ): ResponseInterface {
        $no_redirect = $admin_http instanceof cookie_jar_http_client
            ? $admin_http->with_max_redirects(0)
            : $admin_http;

        $response = $no_redirect->request('POST', '/admin/modules.php', [
            'query' => [
                'set' => self::PAYMENT_SET,
                'action' => 'install',
                'module' => self::PM2CHECKOUT_CODE,
            ],
            'body' => ['formid' => $install_formid],
        ]);
        $this->assertSame(302, $response->getStatusCode());

        return $response;
    }

    private function redirect_location(ResponseInterface $response): string
    {
        $headers = $response->getHeaders(false);
        $location = $headers['location'][0] ?? $headers['Location'][0] ?? '';

        return str_replace('&amp;', '&', $location);
    }

    private function location_includes_module(string $location, string $module_code): bool
    {
        return preg_match(
            '/[?&]module=' . preg_quote($module_code, '/') . '(?:&|#|$)/',
            $location,
        ) === 1;
    }

    private function install_missing_pm2checkout_customer_data_modules(HttpClientInterface $admin_http): void
    {
        $missing_abilities = $this->missing_pm2checkout_abilities($admin_http);
        $this->assertNotEmpty(
            $missing_abilities,
            'Expected at least one missing customer-data ability before installing dependencies.',
        );

        foreach ($missing_abilities as $ability) {
            $module_code = self::CUSTOMER_DATA_MODULE_FOR_ABILITY[$ability] ?? null;
            $this->assertNotNull(
                $module_code,
                'No customer_data module mapped for ability: ' . $ability,
            );
            $this->install_admin_module($admin_http, self::CUSTOMER_DATA_SET, $module_code);
        }

        $this->assertSame([], $this->missing_pm2checkout_abilities($admin_http));
    }

    /**
     * @return list<string>
     */
    private function missing_pm2checkout_abilities(HttpClientInterface $admin_http): array
    {
        $installed_html = $this->fetch_admin_page($admin_http, '/admin/modules.php', [
            'set' => self::CUSTOMER_DATA_SET,
        ]);
        $installed_scope = $this->admin_list_html_for_needle_assertion($installed_html);

        $missing = [];
        foreach (self::PM2CHECKOUT_REQUIRED_ABILITIES as $ability) {
            $module_code = self::CUSTOMER_DATA_MODULE_FOR_ABILITY[$ability];
            if ($this->admin_module_list_needle($installed_scope, $module_code) === '') {
                $missing[] = $ability;
            }
        }

        return $missing;
    }
}
