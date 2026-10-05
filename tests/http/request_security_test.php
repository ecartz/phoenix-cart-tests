<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Exercises Application session security hooks via real HTTP (requires
 * fixtures/http/enable_session_security_checks.sql). Does not call Request::check_* in-process.
 *
 * SESSION_CHECK_SSL_SESSION_ID is not covered here: built-in server runs plain HTTP.
 */
#[Group('http')]
final class request_security_test extends http_test_case {

    private const MISMATCH_USER_AGENT = 'phoenix-cart-tests-http/mismatch-user-agent';

    public function test_changed_user_agent_redirects_to_login(): void {
        $this->assert_logged_in_session_invalidated_after_mismatch(function ($http): ResponseInterface {
            return $http->request('GET', '/account.php', [
                'headers' => [
                    'User-Agent' => self::MISMATCH_USER_AGENT,
                ],
            ]);
        }, true);
    }

    public function test_changed_client_ip_redirects_to_login(): void {
        $this->assert_logged_in_session_invalidated_after_mismatch(function ($http): ResponseInterface {
            return $http->request('GET', '/account.php', [
                'headers' => [
                    'X-Forwarded-For' => '198.51.100.2',
                ],
            ]);
        }, false);
    }

    /**
     * @param callable(\Symfony\Contracts\HttpClient\HttpClientInterface): ResponseInterface $mismatch_request
     */
    private function assert_logged_in_session_invalidated_after_mismatch(
        callable $mismatch_request,
        bool $expect_empty_mismatch_body
    ): void {
        $this->login_fixture_customer();

        $http = $this->get_http_without_redirects();

        $account = $http->request('GET', '/account.php');
        $this->assertSame(200, $account->getStatusCode());
        $this->assertStringContainsString('cm-account-title', $account->getContent(false));

        $mismatch_response = $mismatch_request($http);
        $this->assert_redirect_to_login($mismatch_response);
        if ($expect_empty_mismatch_body) {
            $this->assertSame('', $mismatch_response->getContent(false));
        }

        $retry = $http->request('GET', '/account.php');
        $this->assert_redirect_to_login($retry);
    }

    private function assert_redirect_to_login(ResponseInterface $response): void {
        $this->assertSame(302, $response->getStatusCode());

        $location = $response->getHeaders(false)['location'][0] ?? '';
        $this->assertStringContainsString('login.php', $location);
    }

}
