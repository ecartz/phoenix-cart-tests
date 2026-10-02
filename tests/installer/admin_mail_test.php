<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_mail_capture;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Group('installer')]
final class admin_mail_test extends install_test_case
{
    private const CUSTOMER_FIRSTNAME = 'Mail';

    private const CUSTOMER_LASTNAME = 'Recipient';

    private const CUSTOMER_EMAIL = 'phoenix-install-mail@example.com';

    private const CUSTOMER_PASSWORD = 'phoenix-install-test';

    private const COMPOSE_SUBJECT = 'Phoenix Installer Admin Mail';

    private const COMPOSE_MESSAGE = 'Installer acceptance test message from admin mail.php.';

    private const NEWSLETTER_TITLE = 'Phoenix Installer Mail Newsletter';

    private const NEWSLETTER_CONTENT = 'Installer acceptance test newsletter send body.';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());

        $shop_http = installer_bootstrap::client();
        self::register_storefront_customer($shop_http);
    }

    public function test_admin_compose_mail_reaches_capture(): void
    {
        $this->require_installer_mail_capture();
        $this->clear_captured_mail();

        $admin_http = $this->login_installed_admin();
        $compose = $admin_http->request('GET', '/admin/mail.php');
        $this->assertSame(200, $compose->getStatusCode());
        $compose_html = $compose->getContent(false);
        $formid = self::parse_hidden_input($compose_html, 'formid');
        $this->assertNotSame('', $formid);

        $preview = $admin_http->request('POST', '/admin/mail.php', [
            'query' => ['action' => 'preview'],
            'body' => [
                'formid' => $formid,
                'customers_email_address' => self::CUSTOMER_EMAIL,
                'from_name' => installer_wizard::SAMPLE_STORE_NAME,
                'from_address' => 'installer-test@example.com',
                'subject' => self::COMPOSE_SUBJECT,
                'message' => self::COMPOSE_MESSAGE,
            ],
        ]);
        $this->assertSame(200, $preview->getStatusCode());
        $preview_html = $preview->getContent(false);
        $send_formid = self::parse_hidden_input($preview_html, 'formid');
        $this->assertNotSame('', $send_formid);

        $send = $admin_http->request('POST', '/admin/mail.php', [
            'query' => ['action' => 'send_email_to_user'],
            'body' => [
                'formid' => $send_formid,
                'customers_email_address' => self::CUSTOMER_EMAIL,
                'from_name' => installer_wizard::SAMPLE_STORE_NAME,
                'from_address' => 'installer-test@example.com',
                'subject' => self::COMPOSE_SUBJECT,
                'message' => self::COMPOSE_MESSAGE,
            ],
        ]);
        $this->assertContains($send->getStatusCode(), [200, 302]);

        $this->assert_captured_mail_contains(self::CUSTOMER_EMAIL);

        $captured = installer_mail_capture::read_combined();
        if ($captured !== '') {
            $this->assertStringContainsString(self::COMPOSE_SUBJECT, $captured);
        }
    }

    public function test_admin_newsletter_send_reaches_capture(): void
    {
        $this->require_installer_mail_capture();
        $this->clear_captured_mail();

        $admin_http = $this->login_installed_admin();
        $newsletter_id = $this->insert_newsletter_draft($admin_http);

        $lock = $admin_http->request('GET', '/admin/newsletters.php', [
            'query' => [
                'nID' => $newsletter_id,
                'action' => 'lock',
            ],
        ]);
        $this->assertContains($lock->getStatusCode(), [200, 302]);

        $send_confirm = $admin_http->request('GET', '/admin/newsletters.php', [
            'query' => [
                'nID' => $newsletter_id,
                'action' => 'confirm_send',
            ],
        ]);
        $this->assertSame(200, $send_confirm->getStatusCode());

        $this->assert_captured_mail_contains(self::CUSTOMER_EMAIL);

        $captured = installer_mail_capture::read_combined();
        if ($captured !== '') {
            $this->assertStringContainsString(self::NEWSLETTER_TITLE, $captured);
        }

        $this->delete_newsletter($admin_http, $newsletter_id);
    }

    public function test_admin_order_status_notify_reaches_capture(): void
    {
        $this->require_installer_mail_capture();

        $shop_http = installer_bootstrap::client();
        $this->complete_cod_checkout($shop_http);

        $this->clear_captured_mail();

        $admin_http = $this->login_installed_admin();
        $orders_page = $admin_http->request('GET', '/admin/orders.php');
        $this->assertSame(200, $orders_page->getStatusCode());
        $order_id = $this->parse_order_id_from_orders_html($orders_page->getContent(false));

        $edit_page = $admin_http->request('GET', '/admin/orders.php', [
            'query' => [
                'oID' => $order_id,
                'action' => 'edit',
            ],
        ]);
        $this->assertSame(200, $edit_page->getStatusCode());
        $formid = self::parse_hidden_input($edit_page->getContent(false), 'formid');
        $this->assertNotSame('', $formid);

        $update = $admin_http->request('POST', '/admin/orders.php', [
            'query' => [
                'oID' => $order_id,
                'action' => 'update_order',
            ],
            'body' => [
                'formid' => $formid,
                'status' => '2',
                'comments' => 'Installer notify smoke.',
                'notify' => 'on',
            ],
        ]);
        $this->assertContains($update->getStatusCode(), [200, 302]);

        $this->assert_captured_mail_contains(self::CUSTOMER_EMAIL);
    }

    private function insert_newsletter_draft(HttpClientInterface $admin_http): string
    {
        $new_page = $admin_http->request('GET', '/admin/newsletters.php', [
            'query' => ['action' => 'new'],
        ]);
        $this->assertSame(200, $new_page->getStatusCode());
        $formid = self::parse_hidden_input($new_page->getContent(false), 'formid');
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

        $list = $admin_http->request('GET', '/admin/newsletters.php');
        $this->assertSame(200, $list->getStatusCode());
        $html = $list->getContent(false);
        $this->assertStringContainsString(self::NEWSLETTER_TITLE, $html);

        if (preg_match('/[?&]nID=(\d+)/', $html, $matches) !== 1) {
            $this->fail('newsletters.php did not expose nID after insert');
        }

        return $matches[1];
    }

    private function delete_newsletter(HttpClientInterface $admin_http, string $newsletter_id): void
    {
        $selected = $admin_http->request('GET', '/admin/newsletters.php', [
            'query' => ['nID' => $newsletter_id],
        ]);
        $this->assertSame(200, $selected->getStatusCode());
        $formid = self::parse_formid_from_page($selected->getContent(false));
        $this->assertNotSame('', $formid);

        $delete = $admin_http->request('POST', '/admin/newsletters.php', [
            'query' => [
                'action' => 'delete_confirm',
                'nID' => $newsletter_id,
            ],
            'body' => [
                'formid' => $formid,
            ],
        ]);
        $this->assertContains($delete->getStatusCode(), [200, 302]);
    }

    private static function register_storefront_customer(HttpClientInterface $shop_http): void
    {
        $shop_http->request('GET', '/');

        $create_account_page = $shop_http->request('GET', '/create_account.php');
        if ($create_account_page->getStatusCode() !== 200) {
            throw new \RuntimeException('create_account.php returned HTTP ' . $create_account_page->getStatusCode());
        }

        $create_html = $create_account_page->getContent(false);
        $formid = self::parse_hidden_input($create_html, 'formid');
        if ($formid === '') {
            throw new \RuntimeException('create_account form missing formid');
        }

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
                'newsletter' => '1',
                'matc' => '1',
            ],
        ]);
        if (!in_array($registered->getStatusCode(), [200, 302], true)) {
            throw new \RuntimeException('create_account POST returned HTTP ' . $registered->getStatusCode());
        }
    }

    private function login_storefront_customer(HttpClientInterface $shop_http): void
    {
        $login_page = $shop_http->request('GET', '/login.php');
        $this->assertSame(200, $login_page->getStatusCode());
        $formid = self::parse_hidden_input($login_page->getContent(false), 'formid');
        $this->assertNotSame('', $formid);

        $login = $shop_http->request('POST', '/login.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'email_address' => self::CUSTOMER_EMAIL,
                'password' => self::CUSTOMER_PASSWORD,
            ],
        ]);
        $this->assertContains($login->getStatusCode(), [200, 302]);
    }

    private function complete_cod_checkout(HttpClientInterface $shop_http): void
    {
        $this->login_storefront_customer($shop_http);

        $shop_http->request('GET', '/index.php', [
            'query' => [
                'action' => 'buy_now',
                'products_id' => '3',
            ],
        ]);

        $shipping_page = $shop_http->request('GET', '/checkout_shipping.php');
        $this->assertSame(200, $shipping_page->getStatusCode());
        $shipping_formid = self::parse_hidden_input($shipping_page->getContent(false), 'formid');
        $this->assertNotSame('', $shipping_formid);

        $payment_page = $shop_http->request('POST', '/checkout_shipping.php', [
            'body' => [
                'action' => 'process',
                'formid' => $shipping_formid,
                'shipping' => 'flat_flat',
            ],
        ]);
        $this->assertSame(200, $payment_page->getStatusCode());
        $payment_formid = self::parse_hidden_input($payment_page->getContent(false), 'formid');
        $this->assertNotSame('', $payment_formid);

        $confirmation_page = $shop_http->request('POST', '/checkout_confirmation.php', [
            'body' => [
                'formid' => $payment_formid,
                'payment' => 'cod',
            ],
        ]);
        $this->assertSame(200, $confirmation_page->getStatusCode());
        $confirm_formid = self::parse_hidden_input($confirmation_page->getContent(false), 'formid');
        $this->assertNotSame('', $confirm_formid);

        $success = $shop_http->request('POST', '/checkout_process.php', [
            'body' => [
                'formid' => $confirm_formid,
            ],
        ]);
        $final_url = (string) ($success->getInfo('url') ?? '');
        $this->assertStringContainsString('checkout_success.php', $final_url);
    }

    private function parse_order_id_from_orders_html(string $html): string
    {
        if (preg_match('/[?&]oID=(\d+)/', $html, $matches) === 1) {
            return $matches[1];
        }

        $this->fail('orders.php did not contain an order link with oID');
    }
}
