<?php

declare(strict_types=1);

namespace App\Core\Application\Crud\Search;

use App\Core\Application\Query\QueryHandler;
use LogicException;

class CrudSearchHandler implements QueryHandler
{
    public function __invoke(CrudSearchQuery $query) : CrudSearchResponse
    {
        throw new LogicException(sprintf('%s is not implemented yet.', self::class));
    }
}
