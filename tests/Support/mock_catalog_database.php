<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Support;

/**
 * Minimal double for {@see database_core} methods used by application segments (no mysqli).
 */
final class mock_catalog_database
{
    /**
     * @param list<array<string, string>> $configuration_rows
     * @param array<string, list<array<string, string>>> $table_rows keyed by table name
     */
    public function __construct(
        private array $configuration_rows = [],
        private array $table_rows = [],
    ) {
    }

    /**
     * @param mock_catalog_query_result|string $db_query
     *
     * @return list<array<string, mixed>>
     */
    public function fetch_all($db_query): array
    {
        if ($db_query instanceof mock_catalog_query_result) {
            return $db_query->remaining_rows();
        }

        if (is_string($db_query)) {
            return $this->rows_for_sql($db_query);
        }

        return [];
    }

    /**
     * @param string $query
     */
    public function query($query): mock_catalog_query_result
    {
        return new mock_catalog_query_result($this->rows_for_sql($query));
    }

    public function escape(string $value): string
    {
        return $value;
    }

    public function install_as_global(): self
    {
        $GLOBALS['db'] = $this;

        return $this;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows_for_sql(string $sql): array
    {
        if (preg_match('/\bFROM\s+`?configuration`?\b/i', $sql) === 1) {
            return $this->configuration_rows;
        }

        foreach ($this->table_rows as $table => $rows) {
            $quoted = preg_quote((string) $table, '/');
            if (preg_match('/\bFROM\s+`?' . $quoted . '`?\b/i', $sql) === 1) {
                return $rows;
            }
        }

        return [];
    }
}
