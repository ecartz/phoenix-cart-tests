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
        $response = $admin_http->request('POST', $path, [
            'query' => $query,
            'body' => $body,
        ]);
        $this->assertContains($response->getStatusCode(), [200, 302]);
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
        $this->assertStringContainsString($needle, $html);

        return $html;
    }

    protected function assert_admin_list_not_contains(
        HttpClientInterface $admin_http,
        string $path,
        array $query,
        string $needle,
    ): void {
        $html = $this->fetch_admin_page($admin_http, $path, $query);
        $this->assertStringNotContainsString($needle, $html);
    }

    protected function parse_entity_id_near_needle(string $html, string $needle, string $param): string
    {
        $offset = strpos($html, $needle);
        if ($offset === false) {
            $this->fail('List HTML did not contain: ' . $needle);
        }

        $window = substr($html, max(0, $offset - 400), 800);
        $pattern = '/[?&]' . preg_quote($param, '/') . '=(\d+)/';
        if (preg_match($pattern, $window, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match($pattern, $html, $matches) === 1) {
            return $matches[1];
        }

        $this->fail('Could not parse ' . $param . ' for: ' . $needle);
    }

    protected function parse_id_from_redirect_url(string $url, string $param): string
    {
        if (preg_match('/[?&]' . preg_quote($param, '/') . '=(\d+)/', $url, $matches) === 1) {
            return $matches[1];
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
        $delete_query = array_merge([$id_param => $entity_id, 'action' => $delete_view_action], $extra_query);
        $delete_page = $this->fetch_admin_page($admin_http, $path, $delete_query);
        $formid = self::parse_hidden_input($delete_page, 'formid');
        $this->assertNotSame('', $formid);

        $confirm_query = array_merge([$id_param => $entity_id, 'action' => $delete_confirm_action], $extra_query);
        $this->post_admin_form($admin_http, $path, $confirm_query, array_merge(['formid' => $formid], $extra_body));
    }

    /**
     * @return list<string>
     */
    protected function parse_bracket_language_ids(string $html, string $field_prefix): array
    {
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

    protected function install_and_remove_module(
        HttpClientInterface $admin_http,
        string $set,
        string $module_code,
    ): void {
        $new_modules = $this->fetch_admin_page($admin_http, '/admin/modules.php', [
            'set' => $set,
            'list' => 'new',
            'module' => $module_code,
        ]);
        $this->assertStringContainsString($module_code, $new_modules);
        $install_formid = self::parse_hidden_input($new_modules, 'formid');
        $this->assertNotSame('', $install_formid);

        $this->post_admin_form($admin_http, '/admin/modules.php', [
            'set' => $set,
            'action' => 'install',
            'module' => $module_code,
        ], ['formid' => $install_formid]);

        $installed_html = $this->fetch_admin_page($admin_http, '/admin/modules.php', ['set' => $set]);
        $this->assertStringContainsString($module_code, $installed_html);

        $remove_page = $this->fetch_admin_page($admin_http, '/admin/modules.php', [
            'set' => $set,
            'module' => $module_code,
        ]);
        $remove_formid = self::parse_hidden_input($remove_page, 'formid');
        $this->assertNotSame('', $remove_formid);

        $this->post_admin_form($admin_http, '/admin/modules.php', [
            'set' => $set,
            'action' => 'remove',
            'module' => $module_code,
        ], ['formid' => $remove_formid]);

        $after_remove = $this->fetch_admin_page($admin_http, '/admin/modules.php', ['set' => $set]);
        $this->assertStringNotContainsString($module_code, $after_remove);
    }

    protected function first_new_module_code(HttpClientInterface $admin_http, string $set): ?string
    {
        $html = $this->fetch_admin_page($admin_http, '/admin/modules.php', [
            'set' => $set,
            'list' => 'new',
        ]);

        if (preg_match_all('/[?&]module=([a-z0-9_]+)/', $html, $matches) !== false && $matches[1] !== []) {
            return $matches[1][0];
        }

        return null;
    }

    private function mutated_configuration_value(string $original): string
    {
        if ($original !== '' && is_numeric($original)) {
            return (string) ((int) $original + 1);
        }

        if ($original === '') {
            return '1';
        }

        return $original . 'X';
    }
}
