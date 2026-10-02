<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\https;

use PhoenixCart\Tests\support\http_bootstrap;
use PhoenixCart\Tests\support\https_bootstrap;
use PhoenixCart\Tests\support\phoenix_test_case;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpClient\HttpClient;

#[Group('https')]
final class ssl_session_id_test extends phoenix_test_case {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        if (!https_bootstrap::is_enabled()) {
            self::markTestSkipped(
                'HTTPS tests skipped. Set PHOENIX_HTTPS_ENABLED=1, import fixtures, and start scripts/https-server.sh.'
            );
        }

        if (!http_bootstrap::is_enabled()) {
            self::markTestSkipped(
                'HTTPS tests require PHOENIX_HTTP_ENABLED=1 so the shop configure is written for the catalog.'
            );
        }

        https_bootstrap::apply_ssl_session_check_sql();
        http_bootstrap::write_local_configure();

        $probe = HttpClient::create([
            'base_uri' => https_bootstrap::base_url(),
            'verify_host' => false,
            'verify_peer' => false,
        ]);

        try {
            $response = $probe->request('GET', '/');
            if ($response->getStatusCode() >= 500) {
                throw new \RuntimeException('Shop returned HTTP ' . $response->getStatusCode());
            }
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                'HTTPS shop not reachable at ' . https_bootstrap::base_url() . ': ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    public function test_changed_ssl_session_id_redirects_to_ssl_check(): void {
        $base = https_bootstrap::base_url() . '/';
        $jar = tempnam(sys_get_temp_dir(), 'phoenix_ssl_cookies_');
        $this->assertNotFalse($jar);

        try {
            $first = self::curl_headers($base, $jar, false);
            $this->assertSame(0, $first['exit'], $first['output']);

            $second = self::curl_headers($base, $jar, true);
            $this->assertSame(0, $second['exit'], $second['output']);

            $status = self::parse_status_line($second['headers']);
            $this->assertSame(302, $status, $second['headers']);

            $location = self::parse_header($second['headers'], 'Location');
            $this->assertStringContainsString('ssl_check.php', $location);
        } finally {
            if (is_file($jar)) {
                unlink($jar);
            }
        }
    }

    /**
     * @return array{exit: int, output: string, headers: string}
     */
    protected static function curl_headers(string $url, string $cookie_jar, bool $reuse_cookies): array {
        $sink = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $parts = [
            'curl',
            '-k',
            '-s',
            '-S',
            '-c',
            escapeshellarg($cookie_jar),
        ];

        if ($reuse_cookies) {
            $parts[] = '-b';
            $parts[] = escapeshellarg($cookie_jar);
        }

        $parts[] = '-D';
        $parts[] = '-';
        $parts[] = '-o';
        $parts[] = escapeshellarg($sink);
        $parts[] = escapeshellarg($url);

        $command = implode(' ', $parts);
        $output = [];
        $exit = 0;
        exec($command, $output, $exit);

        $raw = implode("\n", $output);
        $header_block = self::split_response_headers($raw);

        return [
            'exit' => $exit,
            'output' => $raw,
            'headers' => $header_block,
        ];
    }

    protected static function split_response_headers(string $raw): string {
        $parts = preg_split("/\r\n\r\n|\n\n/", $raw, 2);

        return is_array($parts) ? $parts[0] : $raw;
    }

    protected static function parse_status_line(string $headers): int {
        return preg_match('/^HTTP\/\S+\s+(\d+)/m', $headers, $matches)
             ? (int) $matches[1] : 0;
    }

    protected static function parse_header(string $headers, string $name): string {
        return preg_match('/^' . preg_quote($name, '/') . ':\s*(.+)$/mi', $headers, $matches)
             ? trim($matches[1]) : '';
    }

}
