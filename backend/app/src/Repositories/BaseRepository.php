<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Framework\Database;

abstract class BaseRepository
{
    protected \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Fetch a single row and hydrate via the given callable, or return null.
     */
    protected function fetchOneOrNull(\PDOStatement $stmt, callable $hydrator): mixed
    {
        $row = $stmt->fetch();

        return $row ? $hydrator($row) : null;
    }

    /**
     * Fetch all rows and hydrate each via the given callable.
     *
     * @return array<mixed>
     */
    protected function fetchAllHydrated(\PDOStatement $stmt, callable $hydrator): array
    {
        return array_map($hydrator, $stmt->fetchAll());
    }
}
