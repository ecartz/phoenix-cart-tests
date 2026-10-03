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
        private int $max_redirects = 10,
    ) {
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface {
        $redirects_remaining = $this->max_redirects;
        $current_method = $method;
        $current_url = $url;
        $current_options = $options;

        while (true) {
            if ($this->cookies !== []) {
                $current_options['headers'] = array_merge(
                    $current_options['headers'] ?? [],
                    ['Cookie' => $this->format_cookie_header()],
                );
            }

            $response = $this->client->request($current_method, $current_url, $current_options);
            $status = $response->getStatusCode();
            $this->absorb_cookies($response->getHeaders(false));

            if ($redirects_remaining <= 0 || $status < 300 || $status >= 400) {
                return $response;
            }

            $location = $response->getHeaders(false)['location'][0] ?? '';
            if ($location === '') {
                return $response;
            }

            --$redirects_remaining;
            $current_method = 'GET';
            $current_url = $location;
            $current_options = [
                'headers' => $options['headers'] ?? [],
            ];
        }
    }

    public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface {
        return $this->client->stream($responses, $timeout);
    }

    public function withOptions(array $options): static {
        $clone = new self($this->client->withOptions($options), $this->max_redirects);
        $clone->cookies = $this->cookies;

        return $clone;
    }

    public function with_max_redirects(int $max_redirects): self {
        $clone = new self($this->client, $max_redirects);
        $clone->cookies = $this->cookies;

        return $clone;
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private function absorb_cookies(array $headers): void {
        foreach ($headers['set-cookie'] ?? [] as $line) {
            $pair = explode(';', $line, 2)[0];
            $name_value = explode('=', trim($pair), 2);
            if (count($name_value) === 2) {
                $this->cookies[$name_value[0]] = $name_value[1];
            }
        }
    }

    private function format_cookie_header(): string {
        $parts = [];
        foreach ($this->cookies as $name => $value) {
            $parts[] = $name . '=' . $value;
        }

        return implode('; ', $parts);
    }
}
