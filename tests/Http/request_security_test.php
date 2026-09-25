<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Http;

use PhoenixCart\Tests\Support\http_test_case;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Exercises Application session security hooks via real HTTP (requires
 * fixtures/http/enable_session_security_checks.sql). Does not call Request::check_* in-process.
 *
 * SESSION_CHECK_SSL_SESSION_ID is not covered here: built-in server runs plain HTTP.
 */
#[Group('http')]
final class request_security_test extends http_test_case
{
    public function test_changed_user_agent_redirects_to_login(): void
    {
        $http = $this->get_http_without_redirects();
        $http->request('GET', '/');

        $response = $http->request('GET', '/index.php', [
            'headers' => [
                'User-Agent' => 'phoenix-cart-tests-http/mismatch-user-agent',
            ],
        ]);

        $this->assert_redirect_to_login($response);
    }

    public function test_changed_client_ip_redirects_to_login(): void
    {
        $http = $this->get_http_without_redirects();
        $http->request('GET', '/');

        $response = $http->request('GET', '/index.php', [
            'headers' => [
                'X-Forwarded-For' => '198.51.100.2',
            ],
        ]);

        $this->assert_redirect_to_login($response);
    }

    private function assert_redirect_to_login(ResponseInterface $response): void
    {
        $this->assertSame(302, $response->getStatusCode());

        $location = $response->getHeaders(false)['location'][0] ?? '';
        $this->assertStringContainsString('login.php', $location);
    }
}
