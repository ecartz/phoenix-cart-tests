<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_admin_writes;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('installer')]
final class admin_modules_config_test extends install_test_case
{
    use installer_admin_writes;

    /**
     * @return list<string>
     */
    private const MODULE_SETS = [
        'action_recorder',
        'boxes',
        'content',
        'customer_data',
        'dashboard',
        'header_tags',
        'layout',
        'navbar_modules',
        'notifications',
        'order_total',
        'payment',
        'shipping',
    ];

    /**
     * @return list<string>
     */
    private const CONFIGURATION_GROUPS = [
        '4',
        '7',
        '8',
        '9',
        '10',
        '12',
        '13',
        '14',
        '15',
        '16',
    ];

    /**
     * Sample data pre-installs all modules in these sets (no list=new candidates).
     *
     * @var list<string>
     */
    private const MODULE_SETS_SAMPLE_PREINSTALLS_ALL = [
        'action_recorder',
        'header_tags',
        'order_total',
    ];

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());
        self::prepare_module_sets_for_install_tests();
    }

    private static function prepare_module_sets_for_install_tests(): void {
        $runner = new self('prepare_module_sets_for_install_tests');
        $runner->setUp();
        $admin_http = $runner->login_installed_admin();
        foreach (self::MODULE_SETS_SAMPLE_PREINSTALLS_ALL as $set) {
            $runner->ensure_module_set_has_new_candidate($admin_http, $set);
        }
    }

    #[DataProvider('module_set_provider')]
    public function test_admin_module_set_install_and_remove(string $set): void {
        $admin_http = $this->login_installed_admin();
        $module_code = $this->first_new_module_code($admin_http, $set);
        if ($module_code === null) {
            $this->markTestSkipped('No modules available on list=new for set ' . $set);
        }

        $this->install_and_remove_module($admin_http, $set, $module_code);
    }

    #[DataProvider('configuration_group_provider')]
    public function test_admin_configuration_group_round_trip(string $group_id): void {
        $admin_http = $this->login_installed_admin();
        $this->round_trip_configuration_group($admin_http, $group_id);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function module_set_provider(): iterable {
        foreach (self::MODULE_SETS as $set) {
            yield $set => [$set];
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function configuration_group_provider(): iterable {
        foreach (self::CONFIGURATION_GROUPS as $group_id) {
            yield 'group_' . $group_id => [$group_id];
        }
    }
}
