<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class http_test_case extends phoenix_test_case
{
    protected const FIXTURE_CUSTOMER_EMAIL = 'phoenix-http-fixture@example.com';

    protected const FIXTURE_CUSTOMER_PASSWORD = 'phoenix-test';

    private HttpClientInterface $http;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!http_bootstrap::is_enabled()) {
            self::markTestSkipped(
                'HTTP tests skipped. Set PHOENIX_HTTP_ENABLED=1, import fixtures, and start scripts/http-server.sh.'
            );
        }

        http_bootstrap::write_local_configure();

        $probe = http_bootstrap::client();

        try {
            $response = $probe->request('GET', '/');
            $status = $response->getStatusCode();
            if ($status >= 500) {
                throw new \RuntimeException('Shop returned HTTP ' . $status);
            }
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                'HTTP shop not reachable at ' . http_bootstrap::base_url() . ': ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->http = http_bootstrap::client();
    }

    protected function get_http(): HttpClientInterface
    {
        return $this->http;
    }

    protected function get_http_without_redirects(): HttpClientInterface
    {
        return http_bootstrap::client(0);
    }

    protected function login_fixture_customer(): void
    {
        $this->get_http()->request('GET', '/');

        $login_page = $this->get_http()->request('GET', '/login.php');
        $this->assertSame(200, $login_page->getStatusCode());

        $html = $login_page->getContent(false);
        $formid = self::parse_hidden_input($html, 'formid');
        $this->assertNotSame('', $formid, 'login form must expose formid hidden input');

        $response = $this->get_http()->request('POST', '/login.php', [
            'body' => [
                'action' => 'process',
                'formid' => $formid,
                'email_address' => self::FIXTURE_CUSTOMER_EMAIL,
                'password' => self::FIXTURE_CUSTOMER_PASSWORD,
            ],
        ]);

        $status = $response->getStatusCode();
        $this->assertContains($status, [200, 302], 'login POST should succeed or redirect');

        $account_probe = $this->get_http()->request('GET', '/account.php');
        $account_body = $account_probe->getContent(false);
        $this->assertStringContainsString(
            'cm-account-title',
            $account_body,
            'fixture customer login must reach account dashboard'
        );
    }

    protected static function parse_hidden_input(string $html, string $name): string
    {
        $quoted = preg_quote($name, '/');

        if (preg_match('/name="' . $quoted . '"[^>]*\svalue="([^"]*)"/', $html, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/value="([^"]*)"[^>]*\sname="' . $quoted . '"/', $html, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }
}
