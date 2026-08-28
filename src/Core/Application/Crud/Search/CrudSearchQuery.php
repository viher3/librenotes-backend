<?php

declare(strict_types=1);

namespace App\Core\Application\Crud\Search;

use App\Core\Application\Query\SearchQuery;

class CrudSearchQuery extends SearchQuery
{
    public function __construct(
        protected array $filters = [],
        protected ?int $page = self::DEFAULT_PAGE,
        protected ?int $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE,
        protected ?string $orderBy = null,
        protected ?string $orderDirection = null,
        protected array $fieldsToDisplay = []
        // TODO: add custom params
    ) {
        parent::__construct($filters, $page, $itemsPerPage, $orderBy, $orderDirection);
    }
}
