<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;

#[Group('installer')]
final class install_wizard_test extends install_test_case
{
    public function test_web_installer_configures_shop_and_admin_login(): void
    {
        $welcome = $this->get_http()->request('GET', '/install/index.php');
        $this->assertSame(200, $welcome->getStatusCode());
        $welcome_body = $welcome->getContent(false);
        $this->assertStringContainsString('Welcome to Phoenix Cart', $welcome_body);
        $this->assertStringNotContainsString('table-danger', $welcome_body);

        installer_wizard::install_sample_shop($this->get_http());

        $admin_http = $this->login_installed_admin();

        $admin_home = $admin_http->request('GET', '/admin/index.php');
        $this->assertSame(200, $admin_home->getStatusCode());
        $admin_home_url = (string) ($admin_home->getInfo('url') ?? '');
        $this->assertStringNotContainsString('login.php', $admin_home_url);
        $this->assertStringContainsString('display-4', $admin_home->getContent(false));
    }
}
