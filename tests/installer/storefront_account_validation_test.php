<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\installer;

use PhoenixCart\Tests\support\install_test_case;
use PhoenixCart\Tests\support\installer_admin_writes;
use PhoenixCart\Tests\support\installer_bootstrap;
use PhoenixCart\Tests\support\installer_wizard;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[Group('installer')]
final class storefront_account_validation_test extends install_test_case {

    use installer_admin_writes;

    private const PASSWORD = 'phoenix-install-test';

    private const PASSWORD_OTHER = 'phoenix-install-other';

    private const UNITED_STATES_ID = '223';

    private const UNITED_KINGDOM_ID = '222';

    private const FLORIDA_ZONE_NAME = 'Florida';

    private const FREE_TEXT_STATE = 'Testshire';

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        installer_wizard::install_sample_shop(installer_bootstrap::client());

        $runner = new self('install_password_confirmation');
        $runner->setUp();
        $admin_http = $runner->login_installed_admin();
        $runner->install_admin_module($admin_http, 'customer_data', 'cd_password_confirmation');
    }

    public function test_create_account_rejects_empty_required_firstname(): void {
        $email = $this->unique_email('empty');
        $shop_http = installer_bootstrap::client();
        $response = $this->submit_registration($shop_http, $this->registration_fields($email, [
            'firstname' => '',
        ]));

        $this->assertRegistrationRejected(
            $response,
            $email,
            'Your First Name must contain a minimum of 2 characters.',
        );
    }

    public function test_create_account_rejects_duplicate_email(): void {
        $email = $this->unique_email('duplicate');
        $shop_http = installer_bootstrap::client();
        $created = $this->submit_registration($shop_http, $this->registration_fields($email));
        $this->assertRegistrationSucceeded($created);

        $shop_http->request('GET', '/logoff.php');
        $duplicate = $this->submit_registration($shop_http, $this->registration_fields($email, [
            'firstname' => 'Second',
        ]));

        $this->assertRegistrationRejected(
            $duplicate,
            $email,
            'Your E-mail Address already exists in our records',
            false,
        );
        $this->assertSame(1, $this->customer_count($email));
    }

    public function test_create_account_rejects_password_mismatch(): void {
        $email = $this->unique_email('mismatch');
        $shop_http = installer_bootstrap::client();
        $create_html = $this->fetch_create_account_html($shop_http);
        $this->assertStringContainsString('name="password_confirmation"', $create_html);

        $response = $this->submit_registration($shop_http, $this->registration_fields($email, [
            'password_confirmation' => self::PASSWORD_OTHER,
        ]), $create_html);

        $this->assertRegistrationRejected(
            $response,
            $email,
            'The Password Confirmation must match your Password.',
        );
    }

    public function test_create_account_rejects_omitted_matc(): void {
        $email = $this->unique_email('matc');
        $shop_http = installer_bootstrap::client();
        $response = $this->submit_registration($shop_http, $this->registration_fields($email, [
            'matc' => null,
        ]));

        $this->assertNull($this->address_for_email($email));
        $final_url = (string) ($response->getInfo('url') ?? '');
        $this->assertStringNotContainsString('create_account_success.php', $final_url);

        $status = $response->getStatusCode();
        $body = $response->getContent(false);
        $redisplayed = $status === 200 && str_contains($body, 'name="matc"');
        $failed_closed = $status >= 500 || str_contains($body, 'ENTRY_MATC');
        $this->assertTrue(
            $redisplayed || $failed_closed,
            'Omitted matc should keep the account form or fail closed. HTTP ' . $status,
        );
    }

    public function test_create_account_stores_zone_id_or_free_text_state(): void {
        $zoned_email = $this->unique_email('zone');
        $shop_http = installer_bootstrap::client();
        $zoned = $this->submit_registration($shop_http, $this->registration_fields($zoned_email, [
            'country_id' => self::UNITED_STATES_ID,
            'state' => self::FLORIDA_ZONE_NAME,
        ]));
        $this->assertRegistrationSucceeded($zoned);

        $zoned_address = $this->address_for_email($zoned_email);
        $this->assertIsArray($zoned_address);
        $this->assertSame(self::UNITED_STATES_ID, $zoned_address['entry_country_id']);
        $this->assertSame($this->zone_id_for_country_and_name(self::UNITED_STATES_ID, self::FLORIDA_ZONE_NAME), $zoned_address['entry_zone_id']);
        $this->assertSame('', $zoned_address['entry_state']);

        $shop_http->request('GET', '/logoff.php');

        $free_text_email = $this->unique_email('state');
        $free_text = $this->submit_registration($shop_http, $this->registration_fields($free_text_email, [
            'country_id' => self::UNITED_KINGDOM_ID,
            'state' => self::FREE_TEXT_STATE,
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
        ]));
        $this->assertRegistrationSucceeded($free_text);

        $free_text_address = $this->address_for_email($free_text_email);
        $this->assertIsArray($free_text_address);
        $this->assertSame(self::UNITED_KINGDOM_ID, $free_text_address['entry_country_id']);
        $this->assertSame('0', $free_text_address['entry_zone_id']);
        $this->assertSame(self::FREE_TEXT_STATE, $free_text_address['entry_state']);
    }

    /**
     * @param array<string, string|null> $overrides
     * @return array<string, string>
     */
    private function registration_fields(string $email, array $overrides = []): array {
        $fields = [
            'firstname' => 'Account',
            'lastname' => 'Validator',
            'email_address' => $email,
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'street_address' => '1 Test Street',
            'city' => 'Testville',
            'postcode' => '90210',
            'country_id' => self::UNITED_STATES_ID,
            'state' => self::FLORIDA_ZONE_NAME,
            'telephone' => '555-0199',
            'matc' => '1',
        ];

        foreach ($overrides as $key => $value) {
            if ($value === null) {
                unset($fields[$key]);
                continue;
            }

            $fields[$key] = $value;
        }

        return $fields;
    }

    /**
     * @param array<string, string> $fields
     */
    private function submit_registration(
        HttpClientInterface $shop_http,
        array $fields,
        ?string $create_html = null,
    ): ResponseInterface {
        if ($create_html === null) {
            $create_html = $this->fetch_create_account_html($shop_http);
        }

        $formid = self::parse_hidden_input($create_html, 'formid');
        $this->assertNotSame('', $formid);

        return $shop_http->request('POST', '/create_account.php', [
            'body' => ['action' => 'process', 'formid' => $formid] + $fields,
        ]);
    }

    private function fetch_create_account_html(HttpClientInterface $shop_http): string {
        $create_account_page = $shop_http->request('GET', '/create_account.php');
        $this->assertSame(200, $create_account_page->getStatusCode());

        return $create_account_page->getContent(false);
    }

    private function assertRegistrationSucceeded(ResponseInterface $response): void {
        $this->assertContains($response->getStatusCode(), [200, 302]);
        $final_url = (string) ($response->getInfo('url') ?? '');
        $this->assertStringContainsString('create_account_success.php', $final_url);
    }

    private function assertRegistrationRejected(
        ResponseInterface $response,
        string $email,
        string $error_text,
        bool $expect_absent = true,
    ): void {
        $this->assertContains($response->getStatusCode(), [200, 302]);
        $final_url = (string) ($response->getInfo('url') ?? '');
        $this->assertStringNotContainsString('create_account_success.php', $final_url);
        $this->assertStringContainsString($error_text, $response->getContent(false));
        if ($expect_absent) {
            $this->assertNull($this->address_for_email($email));
        }
    }

    private function unique_email(string $label): string {
        return 'phoenix-install-acct-' . $label . '-' . bin2hex(random_bytes(4)) . '@example.com';
    }

    private function customer_count(string $email): int {
        $row = $this->select_one(
            'SELECT COUNT(*) AS total FROM customers WHERE customers_email_address = ?',
            $email,
        );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return array{entry_country_id: string, entry_zone_id: string, entry_state: string}|null
     */
    private function address_for_email(string $email): ?array {
        $row = $this->select_one(
            'SELECT ab.entry_country_id, ab.entry_zone_id, ab.entry_state'
            . ' FROM customers c'
            . ' INNER JOIN address_book ab ON ab.customers_id = c.customers_id'
            . ' WHERE c.customers_email_address = ?'
            . ' ORDER BY ab.address_book_id DESC'
            . ' LIMIT 1',
            $email,
        );
        if ($row === null) {
            return null;
        }

        return [
            'entry_country_id' => (string) $row['entry_country_id'],
            'entry_zone_id' => (string) $row['entry_zone_id'],
            'entry_state' => (string) ($row['entry_state'] ?? ''),
        ];
    }

    private function zone_id_for_country_and_name(string $country_id, string $zone_name): string {
        $mysqli = $this->installer_mysqli();
        $statement = $mysqli->prepare(
            'SELECT zone_id FROM zones WHERE zone_country_id = ? AND zone_name = ? LIMIT 1',
        );
        if ($statement === false) {
            $mysqli->close();
            $this->fail('Prepare failed: ' . $mysqli->error);
        }

        $country = (int) $country_id;
        $statement->bind_param('is', $country, $zone_name);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result !== false ? $result->fetch_assoc() : false;
        $statement->close();
        $mysqli->close();

        if (!is_array($row)) {
            $this->fail('Zone not found for country ' . $country_id . ' name ' . $zone_name);
        }

        return (string) $row['zone_id'];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function select_one(string $sql, string $email): ?array {
        $mysqli = $this->installer_mysqli();
        $statement = $mysqli->prepare($sql);
        if ($statement === false) {
            $error = $mysqli->error;
            $mysqli->close();
            $this->fail('Prepare failed: ' . $error);
        }

        $statement->bind_param('s', $email);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result !== false ? $result->fetch_assoc() : false;
        $statement->close();
        $mysqli->close();

        return is_array($row) ? $row : null;
    }

    private function installer_mysqli(): \mysqli {
        $mysqli = new \mysqli(
            installer_bootstrap::db_host(),
            installer_bootstrap::db_user(),
            installer_bootstrap::db_password(),
            installer_bootstrap::installer_db_name(),
            (int) installer_bootstrap::db_port(),
        );

        if ($mysqli->connect_errno) {
            $this->fail('MySQL connect failed: ' . $mysqli->connect_error);
        }

        $mysqli->set_charset('utf8mb4');

        return $mysqli;
    }

}
