<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Support;

use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class http_test_case extends phoenix_test_case
{
    protected static HttpClientInterface $http;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!http_bootstrap::is_enabled()) {
            self::markTestSkipped(
                'HTTP tests skipped. Set PHOENIX_HTTP_ENABLED=1, import fixtures, and start scripts/http-server.sh.'
            );
        }

        http_bootstrap::write_local_configure();
        self::$http = http_bootstrap::client();

        try {
            $response = self::$http->request('GET', '/');
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

    protected function get_http(): HttpClientInterface
    {
        return self::$http;
    }
}
