<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Read installer outgoing queue rows for mail assertions when sendmail capture is empty.
 */
final class installer_outgoing_lookup
{
    public static function combined_body(): string
    {
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
            'SELECT email_address, fname, slug FROM outgoing ORDER BY id DESC LIMIT 50'
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
            $parts[] = (string) $row['slug'];
        }

        $result->free();
        $mysqli->close();

        return implode("\n", $parts);
    }
}
