<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\Support;

/**
 * Minimal double for {@see database_core} methods used by application segments (no mysqli).
 */
final class mock_catalog_database {

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
     * @param mysqli_result|string $db_query
     *
     * @return list<array<string, string>>
     */
    public function fetch_all($db_query): array
    {
        if (is_string($db_query)) {
            if (str_contains($db_query, 'FROM configuration')) {
                return $this->configuration_rows;
            }

            foreach ($this->table_rows as $table => $rows) {
                if (str_contains($db_query, 'FROM ' . $table) || str_contains($db_query, 'FROM `' . $table . '`')) {
                    return $rows;
                }
            }

            return [];
        }

        return [];
    }

    /**
     * @param string $query
     *
     * @return list<array<string, string>>
     */
    public function query($query)
    {
        return $this->fetch_all($query);
    }

    public function escape(string $value): string
    {
        return $value;
    }

}
