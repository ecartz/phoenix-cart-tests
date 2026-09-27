<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class http_test_case extends phoenix_test_case
{
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
}
