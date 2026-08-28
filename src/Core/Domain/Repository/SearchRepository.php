<?php

namespace App\Core\Domain\Repository;

interface SearchRepository
{
    /**
     * @param array $filters
     * @param integer $page
     * @param integer $perPage
     * @param string|null $orderBy
     * @param string|null $orderDirection
     * @return array
     */
    public function search(
        array $filters,
        int $page,
        int $perPage,
        ?string $orderBy = null,
        ?string $orderDirection = null
    ): array;

    /**
     * @param array $filters
     * @return integer
     */
    public function searchCount(array $filters): int;
}
