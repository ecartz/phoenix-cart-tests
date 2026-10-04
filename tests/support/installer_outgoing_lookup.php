<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Read installer outgoing queue rows for mail assertions when sendmail capture is empty.
 */
final class installer_outgoing_lookup {

    public static function combined_body(): string {
        mysql_bootstrap::define_connection_constants();

        $database = getenv('PHOENIX_INSTALLER_DB_NAME');
        if (!is_string($database) || $database === '') {
            $database = 'phoenix_install';
        }

        $mysqli = new \mysqli(
            (string) (getenv('PHOENIX_DB_HOST') ?: '127.0.0.1'),
            (string) (getenv('PHOENIX_DB_USER') ?: 'phoenix'),
            (string) (getenv('PHOENIX_DB_PASSWORD') ?: 'phoenix'),
            $database
        );

        if ($mysqli->connect_errno) {
            return '';
        }

        $mysqli->set_charset('utf8mb4');

        $result = $mysqli->query(
            'SELECT email_address, fname, lname, slug, merge_tags FROM outgoing ORDER BY id DESC LIMIT 50'
        );

        if ($result === false) {
            $mysqli->close();

            return '';
        }

        $parts = [];
        while ($row = $result->fetch_assoc()) {
            if (!is_array($row)) {
                continue;
            }

            $parts[] = (string) $row['email_address'];
            $parts[] = (string) $row['fname'];
            $parts[] = (string) $row['lname'];
            $parts[] = (string) $row['slug'];
            $merge_tags = (string) $row['merge_tags'];
            if ($merge_tags !== '') {
                $parts[] = $merge_tags;
                $decoded = json_decode($merge_tags, true);
                if (is_array($decoded)) {
                    array_walk_recursive($decoded, static function ($value) use (&$parts): void {
                        if (is_string($value) && $value !== '') {
                            $parts[] = $value;
                        }
                    });
                }
            }
        }

        $result->free();
        $mysqli->close();

        return implode("\n", $parts);
    }

    public static function has_queued_row(string $email_address, string $slug): bool {
        if ($email_address === '' || $slug === '') {
            return false;
        }

        mysql_bootstrap::define_connection_constants();

        $database = getenv('PHOENIX_INSTALLER_DB_NAME');
        if (!is_string($database) || $database === '') {
            $database = 'phoenix_install';
        }

        $mysqli = new \mysqli(
            (string) (getenv('PHOENIX_DB_HOST') ?: '127.0.0.1'),
            (string) (getenv('PHOENIX_DB_USER') ?: 'phoenix'),
            (string) (getenv('PHOENIX_DB_PASSWORD') ?: 'phoenix'),
            $database
        );

        if ($mysqli->connect_errno) {
            return false;
        }

        $mysqli->set_charset('utf8mb4');

        $statement = $mysqli->prepare(
            'SELECT 1 FROM outgoing WHERE email_address = ? AND slug = ? LIMIT 1'
        );
        if ($statement === false) {
            $mysqli->close();

            return false;
        }

        $statement->bind_param('ss', $email_address, $slug);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result !== false ? $result->fetch_assoc() : false;
        $statement->close();
        $mysqli->close();

        return is_array($row);
    }

}
