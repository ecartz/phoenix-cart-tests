<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\support;

/**
 * Minimal stand-in for mysqli_result used by {@see mock_catalog_database::query()}.
 *
 * Supports {@see fetch_assoc()} only. Does not satisfy mysqli_num_rows() (wave 3).
 */
final class mock_catalog_query_result {

    /** @var list<array<string, mixed>> */
    private array $rows;

    private int $position = 0;

    public int $num_rows;

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function __construct(array $rows = []) {
        $this->rows = array_values($rows);
        $this->num_rows = count($this->rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetch_assoc(): ?array {
        if ($this->position >= count($this->rows)) {
            return null;
        }

        return $this->rows[$this->position++];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all_rows(): array {
        return $this->rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function remaining_rows(): array {
        return array_slice($this->rows, $this->position);
    }

}
