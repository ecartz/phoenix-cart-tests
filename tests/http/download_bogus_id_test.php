<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\http;

use PhoenixCart\Tests\support\http_test_case;
use PHPUnit\Framework\Attributes\Group;

#[Group('http')]
final class download_bogus_id_test extends http_test_case {

    public function test_bogus_download_id_does_not_return_file_payload(): void {
        $this->login_fixture_customer();

        $response = $this->get_http()->request('GET', '/download.php', [
            'query' => [
                'order' => '999999',
                'id' => '999999',
            ],
        ]);

        $this->assertContains($response->getStatusCode(), [200, 302, 403]);
        $body = $response->getContent(false);
        $this->assertStringNotContainsString('PK', $body);
        $this->assertStringNotContainsString('phoenix-http-download-fixture-payload', $body);
    }

}
