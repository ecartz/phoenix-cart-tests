<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

trait installer_admin_writes
{
    protected function fetch_admin_page(
        HttpClientInterface $admin_http,
        string $path,
        array $query = [],
    ): string {
        $response = $admin_http->request('GET', $path, ['query' => $query]);
        $this->assertSame(200, $response->getStatusCode());

        return $response->getContent(false);
    }

    protected function post_admin_form(
        HttpClientInterface $admin_http,
        string $path,
        array $query,
        array $body,
    ): void {
        $this->post_admin_form_response($admin_http, $path, $query, $body);
    }

    protected function post_admin_form_response(
        HttpClientInterface $admin_http,
        string $path,
        array $query,
        array $body,
    ): \Symfony\Contracts\HttpClient\ResponseInterface {
        $response = $admin_http->request('POST', $path, [
            'query' => $query,
            'body' => $body,
        ]);
        $this->assertContains($response->getStatusCode(), [200, 302]);

        return $response;
    }

    protected function parse_admin_modules_set_from_response(
        \Symfony\Contracts\HttpClient\ResponseInterface $response,
    ): ?string {
        if ($response->getStatusCode() !== 302) {
            return null;
        }

        $headers = $response->getHeaders(false);
        $location = $headers['location'][0] ?? $headers['Location'][0] ?? '';
        $location = str_replace('&amp;', '&', $location);

        if (preg_match('/[?&]set=([a-z0-9_]+)/', $location, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @param array<string, string> $fields
     * @param array<string, string> $files field name => absolute file path
     */
    protected function post_admin_multipart(
        HttpClientInterface $admin_http,
        string $path,
        array $query,
        array $fields,
        array $files = [],
    ): void {
        $body = $fields;
        foreach ($files as $name => $file_path) {
            $body[$name] = DataPart::fromPath($file_path);
        }

        $form_data = new FormDataPart($body);
        $response = $admin_http->request('POST', $path, [
            'query' => $query,
            'headers' => $form_data->getPreparedHeaders()->toArray(),
            'body' => $form_data->bodyToIterable(),
        ]);
        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    protected function assert_admin_list_contains(
        HttpClientInterface $admin_http,
        string $path,
        array $query,
        string $needle,
    ): string {
        $html = $this->fetch_admin_page($admin_http, $path, $query);
        $this->assertStringContainsString($needle, $this->admin_list_html_for_needle_assertion($html));

        return $html;
    }

    protected function assert_admin_list_not_contains(
        HttpClientInterface $admin_http,
        string $path,
        array $query,
        string $needle,
    ): void {
        $html = $this->fetch_admin_page($admin_http, $path, $query);
        $this->assertStringNotContainsString($needle, $this->admin_list_html_for_needle_assertion($html));
    }

    protected function admin_list_html_for_needle_assertion(string $html): string {
        $html = (string) preg_replace('/<input\b(?:(?!>).)*\bname="search"(?:(?!>).)*>/i', '', $html);

        return $this->admin_list_table_body($html);
    }

    protected function parse_entity_id_near_needle(string $html, string $needle, string $param): string {
        $html = str_replace('&amp;', '&', $html);
        $list_html = $this->admin_list_table_body($html);
        if (!str_contains($list_html, $needle)) {
            $this->fail('List HTML did not contain: ' . $needle);
        }

        $entity_id = $this->parse_entity_id_from_table_row_containing($list_html, $needle, $param);
        if ($entity_id !== '') {
            return $entity_id;
        }

        $this->fail('Could not parse ' . $param . ' for: ' . $needle);
    }

    protected function admin_list_table_body(string $html): string {
        if (preg_match(
            '/<table class="table table-striped table-hover">\s*<thead class="table-dark">.*?<tbody>(.*?)<\/tbody>/s',
            $html,
            $matches,
        ) === 1) {
            return $matches[1];
        }

        return $html;
    }

    protected function parse_entity_id_from_table_row_containing(
        string $html,
        string $needle,
        string $param,
    ): string {
        $html = str_replace('&amp;', '&', $html);

        if (preg_match_all('/<tr\b[^>]*>.*?<\/tr>/s', $html, $rows) === false) {
            return '';
        }

        $pattern = '/[?&]' . preg_quote($param, '/') . '=(\d+)/';
        $onclick_pattern = '/onclick="document\.location\.href=\'[^\']*[?&]'
            . preg_quote($param, '/')
            . '=(\d+)/';
        foreach ($rows[0] as $row) {
            if (!str_contains($row, $needle)) {
                continue;
            }

            if (preg_match($onclick_pattern, $row, $matches) === 1) {
                return $matches[1];
            }

            if (preg_match($pattern, $row, $matches) === 1) {
                return $matches[1];
            }
        }

        return '';
    }

    protected function parse_entity_id_from_table_row_onclick(
        string $html,
        string $needle,
        string $param,
    ): string {
        return $this->parse_entity_id_from_table_row_containing($html, $needle, $param);
    }

    /**
     * @param array<string, string> $overrides
     *
     * @return array<string, string>
     */
    protected function parse_admin_edit_form_body(string $html, array $overrides = []): array {
        $body = [];

        if (preg_match_all('/<input[^>]+name="([^"]+)"[^>]*>/', $html, $inputs, PREG_SET_ORDER) !== false) {
            foreach ($inputs as $input) {
                $name = $input[1];
                if (!preg_match('/\btype="([^"]+)"/', $input[0], $type_match)) {
                    $type = 'text';
                } else {
                    $type = strtolower($type_match[1]);
                }
                if ($type === 'submit' || $type === 'button' || $type === 'image') {
                    continue;
                }

                if ($type === 'checkbox' || $type === 'radio') {
                    if (!preg_match('/\bchecked(?:="checked")?/', $input[0])) {
                        continue;
                    }
                }

                if (preg_match('/\bvalue="([^"]*)"/', $input[0], $value_match) === 1) {
                    $body[$name] = html_entity_decode($value_match[1], ENT_QUOTES);
                } elseif ($type === 'checkbox' || $type === 'radio') {
                    $body[$name] = 'on';
                }
            }
        }

        if (preg_match_all('/<textarea[^>]+name="([^"]+)"[^>]*>(.*?)<\/textarea>/s', $html, $textareas, PREG_SET_ORDER) !== false) {
            foreach ($textareas as $textarea) {
                $body[$textarea[1]] = html_entity_decode($textarea[2], ENT_QUOTES);
            }
        }

        if (preg_match_all('/<select[^>]+name="([^"]+)"[^>]*>(.*?)<\/select>/s', $html, $selects, PREG_SET_ORDER) !== false) {
            foreach ($selects as $select) {
                if (preg_match('/<option[^>]+selected[^>]*value="([^"]*)"/', $select[2], $selected) === 1) {
                    $body[$select[1]] = html_entity_decode($selected[1], ENT_QUOTES);
                } elseif (preg_match('/<option[^>]+value="([^"]*)"[^>]*selected/', $select[2], $selected) === 1) {
                    $body[$select[1]] = html_entity_decode($selected[1], ENT_QUOTES);
                }
            }
        }

        foreach ($overrides as $name => $value) {
            $body[$name] = $value;
        }

        return $body;
    }

    protected static function parse_formid_for_admin_action(string $html, string $action): string {
        $html = html_entity_decode($html, ENT_QUOTES);
        $quoted_action = preg_quote($action, '/');
        if (preg_match(
            '/<form[^>]*action="[^"]*action=' . $quoted_action . '[^"]*"[^>]*>.*?name="formid"[^>]*value="([^"]*)"/s',
            $html,
            $matches,
        ) === 1) {
            return $matches[1];
        }

        if (preg_match(
            '/<form[^>]*action="[^"]*action=' . $quoted_action . '[^"]*"[^>]*>.*?value="([^"]*)"[^>]*name="formid"/s',
            $html,
            $matches,
        ) === 1) {
            return $matches[1];
        }

        return self::parse_formid_from_page($html);
    }

    protected function parse_id_from_redirect_url(string $url, string $param): string {
        if (preg_match('/[?&]' . preg_quote($param, '/') . '=(\d+)/', $url, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    protected function resolve_admin_formid(HttpClientInterface $admin_http): string {
        foreach ([
            '/admin/mail.php',
            '/admin/languages.php',
            '/admin/configuration.php',
            '/admin/index.php',
        ] as $path) {
            $query = match ($path) {
                '/admin/configuration.php' => ['gID' => '1'],
                '/admin/languages.php' => ['action' => 'new'],
                default => [],
            };
            $html = $this->fetch_admin_page($admin_http, $path, $query);
            $formid = self::parse_hidden_input($html, 'formid');
            if ($formid === '') {
                $formid = self::parse_formid_from_page($html);
            }
            if ($formid !== '') {
                return $formid;
            }
        }

        return '';
    }

    protected function confirm_admin_delete(
        HttpClientInterface $admin_http,
        string $path,
        string $id_param,
        string $entity_id,
        string $delete_confirm_action = 'delete_confirm',
        array $extra_query = [],
        array $extra_body = [],
        string $delete_view_action = 'delete',
    ): void {
        $this->fetch_admin_page($admin_http, $path, array_merge([$id_param => $entity_id], $extra_query));

        $delete_query = array_merge([$id_param => $entity_id, 'action' => $delete_view_action], $extra_query);
        $delete_page = $this->fetch_admin_page($admin_http, $path, $delete_query);
        $formid = self::parse_formid_from_page($delete_page);
        if ($formid === '') {
            $formid = self::parse_formid_for_admin_action($delete_page, $delete_confirm_action);
        }
        if ($formid === '') {
            $formid = $this->resolve_admin_formid($admin_http);
        }
        $this->assertNotSame('', $formid);

        $confirm_query = array_merge(
            [$id_param => $entity_id, 'action' => $delete_confirm_action],
            $extra_query,
        );
        $this->post_admin_form($admin_http, $path, $confirm_query, array_merge(['formid' => $formid], $extra_body));
    }

    /**
     * @return list<string>
     */
    protected function parse_bracket_language_ids(string $html, string $field_prefix): array {
        $pattern = '/name="' . preg_quote($field_prefix, '/') . '\[(\d+)\]"/';
        if (preg_match_all($pattern, $html, $matches) === false || $matches[1] === []) {
            return ['1'];
        }

        return array_values(array_unique($matches[1]));
    }

    /**
     * @return array{cID: string, original: string, changed: string}|null
     */
    protected function resolve_plain_configuration_entry(
        HttpClientInterface $admin_http,
        string $group_id,
    ): ?array {
        $list_html = $this->fetch_admin_page($admin_http, '/admin/configuration.php', ['gID' => $group_id]);

        if (preg_match_all(
            '/href="([^"]*configuration\.php\?[^"]*cID=(\d+)[^"]*)"/',
            $list_html,
            $matches,
            PREG_SET_ORDER,
        ) === false) {
            return null;
        }

        foreach ($matches as $match) {
            $configuration_id = $match[2];
            $edit_html = $this->fetch_admin_page($admin_http, '/admin/configuration.php', [
                'gID' => $group_id,
                'cID' => $configuration_id,
                'action' => 'edit',
            ]);

            if (!str_contains($edit_html, 'name="configuration_value"')) {
                continue;
            }

            if (preg_match('/name="configuration_value"[^>]*value="([^"]*)"/', $edit_html, $value_match) !== 1
                && preg_match('/value="([^"]*)"[^>]*name="configuration_value"/', $edit_html, $value_match) !== 1) {
                continue;
            }

            $original = html_entity_decode($value_match[1], ENT_QUOTES);
            $changed = $this->mutated_configuration_value($original);

            return [
                'cID' => $configuration_id,
                'original' => $original,
                'changed' => $changed,
            ];
        }

        return null;
    }

    protected function post_configuration_group_value(
        HttpClientInterface $admin_http,
        string $group_id,
        string $configuration_id,
        string $value,
    ): void {
        $edit_html = $this->fetch_admin_page($admin_http, '/admin/configuration.php', [
            'gID' => $group_id,
            'cID' => $configuration_id,
            'action' => 'edit',
        ]);
        $formid = self::parse_hidden_input($edit_html, 'formid');
        $this->assertNotSame('', $formid);

        $this->post_admin_form($admin_http, '/admin/configuration.php', [
            'gID' => $group_id,
            'cID' => $configuration_id,
            'action' => 'save',
        ], [
            'formid' => $formid,
            'configuration_value' => $value,
        ]);
    }

    protected function round_trip_configuration_group(
        HttpClientInterface $admin_http,
        string $group_id,
    ): void {
        $resolved = $this->resolve_plain_configuration_entry($admin_http, $group_id);
        if ($resolved === null) {
            $this->markTestSkipped('No plain configuration_value field found in configuration group ' . $group_id);
        }

        $this->post_configuration_group_value(
            $admin_http,
            $group_id,
            $resolved['cID'],
            $resolved['changed'],
        );
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/configuration.php',
            ['gID' => $group_id],
            $resolved['changed'],
        );

        $this->post_configuration_group_value(
            $admin_http,
            $group_id,
            $resolved['cID'],
            $resolved['original'],
        );
        $this->assert_admin_get_page(
            $admin_http,
            '/admin/configuration.php',
            ['gID' => $group_id],
            $resolved['original'],
        );
    }

    protected function install_admin_module(
        HttpClientInterface $admin_http,
        string $set,
        string $module_code,
    ): string {
        $new_modules = $this->fetch_admin_page($admin_http, '/admin/modules.php', [
            'set' => $set,
            'list' => 'new',
            'module' => $module_code,
        ]);
        $this->assertStringContainsString($module_code, $new_modules);
        $this->assertSame($module_code, self::parse_new_module_code_from_modules_html($new_modules));
        $install_formid = self::parse_hidden_input($new_modules, 'formid');
        $this->assertNotSame('', $install_formid);

        $install_response = $this->post_admin_form_response($admin_http, '/admin/modules.php', [
            'set' => $set,
            'action' => 'install',
            'module' => $module_code,
        ], ['formid' => $install_formid]);

        $list_set = $this->parse_admin_modules_set_from_response($install_response) ?? $set;

        $installed_html = $this->fetch_admin_page($admin_http, '/admin/modules.php', ['set' => $list_set]);
        $this->assert_admin_module_list_contains($installed_html, $module_code);

        return $list_set;
    }

    protected function install_and_remove_module(
        HttpClientInterface $admin_http,
        string $set,
        string $module_code,
    ): void {
        $list_set = $this->install_admin_module($admin_http, $set, $module_code);

        $this->remove_installed_module($admin_http, $list_set, $module_code);

        $after_remove = $this->fetch_admin_page($admin_http, '/admin/modules.php', ['set' => $list_set]);
        $this->assert_admin_module_list_not_contains($after_remove, $module_code);
    }

    protected function remove_installed_module(
        HttpClientInterface $admin_http,
        string $list_set,
        string $module_code,
    ): void {
        $remove_page = $this->fetch_admin_page($admin_http, '/admin/modules.php', [
            'set' => $list_set,
            'module' => $module_code,
        ]);
        $remove_formid = self::parse_hidden_input($remove_page, 'formid');
        $this->assertNotSame('', $remove_formid);

        $this->post_admin_form($admin_http, '/admin/modules.php', [
            'set' => $list_set,
            'action' => 'remove',
            'module' => $module_code,
        ], ['formid' => $remove_formid]);
    }

    protected function assert_admin_module_list_contains(string $html, string $module_code): void {
        $list_html = $this->admin_list_html_for_needle_assertion($html);
        $needle = $this->admin_module_list_needle($list_html, $module_code);
        $this->assertTrue(
            $needle !== '',
            'Module list did not contain: ' . $module_code,
        );
    }

    protected function assert_admin_module_list_not_contains(string $html, string $module_code): void {
        $list_html = $this->admin_list_html_for_needle_assertion($html);
        $needle = $this->admin_module_list_needle($list_html, $module_code);
        $this->assertSame('', $needle, 'Module list still contained: ' . $module_code);
    }

    protected function admin_module_list_needle(string $list_html, string $module_code): string {
        $list_html = str_replace('&amp;', '&', $list_html);

        if (preg_match('/[?&]module=' . preg_quote($module_code, '/') . '(?:&|"|\'|$)/', $list_html) === 1) {
            return $module_code;
        }

        if (preg_match('/\b' . preg_quote($module_code, '/') . '\b/i', $list_html) === 1) {
            return $module_code;
        }

        return '';
    }

    /**
     * Sample import often pre-installs every module in a set, leaving list=new empty.
     * Remove one installed module so install/remove coverage can pick from list=new.
     */
    protected function ensure_module_set_has_new_candidate(HttpClientInterface $admin_http, string $set): void {
        if ($this->first_new_module_code($admin_http, $set) !== null) {
            return;
        }

        $installed_html = $this->fetch_admin_page($admin_http, '/admin/modules.php', ['set' => $set]);
        $installed_codes = self::parse_module_codes_from_modules_table_html($installed_html);
        if ($installed_codes === []) {
            return;
        }

        $this->remove_installed_module(
            $admin_http,
            $set,
            self::installed_module_to_remove_for_new_candidate($set, $installed_codes),
        );
    }

    /**
     * @param list<string> $installed_codes
     */
    protected static function installed_module_to_remove_for_new_candidate(string $set, array $installed_codes): string {
        if ($set === 'action_recorder') {
            foreach ($installed_codes as $module_code) {
                if ($module_code !== 'ar_admin_login') {
                    return $module_code;
                }
            }
        }

        return $installed_codes[0];
    }

    protected function first_new_module_code(HttpClientInterface $admin_http, string $set): ?string {
        $html = $this->fetch_admin_page($admin_http, '/admin/modules.php', [
            'set' => $set,
            'list' => 'new',
        ]);

        $installed_html = $this->fetch_admin_page($admin_http, '/admin/modules.php', ['set' => $set]);
        $installed_scope = $this->admin_list_html_for_needle_assertion($installed_html);

        foreach (self::parse_new_module_codes_from_modules_html($html) as $module_code) {
            if ($this->admin_module_list_needle($installed_scope, $module_code) !== '') {
                continue;
            }

            if (!$this->new_module_detail_page_has_install_form($admin_http, $set, $module_code)) {
                continue;
            }

            $list_set = $this->probe_new_module_install($admin_http, $set, $module_code);
            if ($list_set === null) {
                continue;
            }

            $this->remove_installed_module($admin_http, $list_set, $module_code);

            return $module_code;
        }

        return null;
    }

    protected function new_module_detail_page_has_install_form(
        HttpClientInterface $admin_http,
        string $set,
        string $module_code,
    ): bool {
        $detail_html = $this->fetch_admin_page($admin_http, '/admin/modules.php', [
            'set' => $set,
            'list' => 'new',
            'module' => $module_code,
        ]);

        return self::parse_new_module_code_from_modules_html($detail_html) === $module_code;
    }

    protected function probe_new_module_install(
        HttpClientInterface $admin_http,
        string $set,
        string $module_code,
    ): ?string {
        $detail_html = $this->fetch_admin_page($admin_http, '/admin/modules.php', [
            'set' => $set,
            'list' => 'new',
            'module' => $module_code,
        ]);
        $install_formid = self::parse_hidden_input($detail_html, 'formid');
        if ($install_formid === '') {
            return null;
        }

        $install_response = $this->post_admin_form_response($admin_http, '/admin/modules.php', [
            'set' => $set,
            'action' => 'install',
            'module' => $module_code,
        ], ['formid' => $install_formid]);

        $list_set = $this->parse_admin_modules_set_from_response($install_response) ?? $set;
        $installed_html = $this->fetch_admin_page($admin_http, '/admin/modules.php', ['set' => $list_set]);
        $list_html = $this->admin_list_html_for_needle_assertion($installed_html);
        if ($this->admin_module_list_needle($list_html, $module_code) === '') {
            return null;
        }

        return $list_set;
    }

    protected static function parse_new_module_code_from_modules_html(string $html): ?string {
        $html = str_replace('&amp;', '&', $html);

        if (preg_match(
            '/<form\b[^>]*\bname=(["\'])install_module\1[^>]*\baction=(["\'])([^"\']+)\2/is',
            $html,
            $form_match,
        ) === 1) {
            $module_code = self::parse_module_query_parameter($form_match[3]);
            if ($module_code !== null) {
                return $module_code;
            }
        }

        if (preg_match(
            '/<form\b[^>]*\baction=(["\'])([^"\']+)\1[^>]*\bname=(["\'])install_module\3/is',
            $html,
            $form_match,
        ) === 1) {
            $module_code = self::parse_module_query_parameter($form_match[2]);
            if ($module_code !== null) {
                return $module_code;
            }
        }

        $table_codes = self::parse_module_codes_from_modules_table_html($html);
        if ($table_codes !== []) {
            return $table_codes[0];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected static function parse_new_module_codes_from_modules_html(string $html): array {
        $html = str_replace('&amp;', '&', $html);
        $codes = [];

        foreach ([
            '/<form\b[^>]*\bname=(["\'])install_module\1[^>]*\baction=(["\'])([^"\']+)\2/is',
            '/<form\b[^>]*\baction=(["\'])([^"\']+)\1[^>]*\bname=(["\'])install_module\3/is',
        ] as $index => $pattern) {
            if (preg_match($pattern, $html, $form_match) !== 1) {
                continue;
            }

            $action = $index === 0 ? $form_match[3] : $form_match[2];
            $module_code = self::parse_module_query_parameter($action);
            if ($module_code !== null) {
                $codes[] = $module_code;
            }
        }

        foreach (self::parse_module_codes_from_modules_table_html($html) as $module_code) {
            $codes[] = $module_code;
        }

        return array_values(array_unique($codes));
    }

    /**
     * @return list<string>
     */
    protected static function parse_module_codes_from_modules_table_html(string $html): array {
        $html = str_replace('&amp;', '&', $html);
        if (preg_match(
            '/<table class="table table-striped table-hover">\s*<thead class="table-dark">.*?<tbody>(.*?)<\/tbody>/s',
            $html,
            $matches,
        ) !== 1) {
            return [];
        }

        if (preg_match_all('/[?&]module=([a-z0-9_]+)/', $matches[1], $module_matches) === 0) {
            return [];
        }

        return array_values(array_unique($module_matches[1]));
    }

    protected static function parse_module_query_parameter(string $url): ?string {
        if (preg_match('/[?&]module=([a-z0-9_]+)/', $url, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function mutated_configuration_value(string $original): string {
        if ($original !== '' && is_numeric($original)) {
            return (string) ((int) $original + 1);
        }

        if ($original === '') {
            return '1';
        }

        return $original . 'X';
    }
}
