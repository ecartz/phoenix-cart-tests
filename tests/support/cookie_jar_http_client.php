<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * Persists Set-Cookie across requests (Symfony HttpClient does not do this by default).
 */
final class cookie_jar_http_client implements HttpClientInterface
{
    /** @var array<string, string> */
    private array $cookies = [];

    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if ($this->cookies !== []) {
            $options['headers'] = array_merge(
                $options['headers'] ?? [],
                ['Cookie' => $this->format_cookie_header()],
            );
        }

        $response = $this->client->request($method, $url, $options);
        $this->absorb_cookies($response->getHeaders(false));

        return $response;
    }

    public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    public function withOptions(array $options): static
    {
        $clone = new self($this->client->withOptions($options));
        $clone->cookies = $this->cookies;

        return $clone;
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private function absorb_cookies(array $headers): void
    {
        foreach ($headers['set-cookie'] ?? [] as $line) {
            $pair = explode(';', $line, 2)[0];
            $name_value = explode('=', trim($pair), 2);
            if (count($name_value) === 2) {
                $this->cookies[$name_value[0]] = $name_value[1];
            }
        }
    }

    private function format_cookie_header(): string
    {
        $parts = [];
        foreach ($this->cookies as $name => $value) {
            $parts[] = $name . '=' . $value;
        }

        return implode('; ', $parts);
    }
}
