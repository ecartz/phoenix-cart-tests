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

    /** Catalog string from PhoenixCart admin/includes/languages/english/modules.php */
    private const UNMET_REQUIREMENT_MESSAGE = 'This module has an unmet dependency.';

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

        $this->assert_pm2checkout_install_rejected($admin_http, $missing_before);

        $this->install_missing_pm2checkout_customer_data_modules($admin_http);

        $list_set = $this->install_admin_module($admin_http, self::PAYMENT_SET, self::PM2CHECKOUT_CODE);
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

    /**
     * @param list<string> $expected_missing_abilities
     */
    private function assert_pm2checkout_install_rejected(
        HttpClientInterface $admin_http,
        array $expected_missing_abilities,
    ): void {
        $new_module_html = $this->fetch_admin_page($admin_http, '/admin/modules.php', [
            'set' => self::PAYMENT_SET,
            'list' => 'new',
            'module' => self::PM2CHECKOUT_CODE,
        ]);
        $install_formid = self::parse_hidden_input($new_module_html, 'formid');
        $this->assertNotSame('', $install_formid);

        $install_response = $this->post_admin_form_response($admin_http, '/admin/modules.php', [
            'set' => self::PAYMENT_SET,
            'action' => 'install',
            'module' => self::PM2CHECKOUT_CODE,
        ], ['formid' => $install_formid]);

        $list_set = $this->parse_admin_modules_set_from_response($install_response) ?? self::PAYMENT_SET;
        $after_install_html = $this->fetch_admin_page($admin_http, '/admin/modules.php', ['set' => $list_set]);

        $this->assert_admin_module_list_not_contains($after_install_html, self::PM2CHECKOUT_CODE);
        $this->assertStringContainsString(self::UNMET_REQUIREMENT_MESSAGE, $after_install_html);
        foreach ($expected_missing_abilities as $ability) {
            $this->assertStringContainsString($ability, $after_install_html);
        }
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
