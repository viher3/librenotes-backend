<?php

namespace App\Core\Infrastructure\Framework\Symfony\Controller;

use App\Core\Infrastructure\Filter\ApiFilter;
use App\Core\Infrastructure\Filter\ApiFilterCollection;

class RequestFiltersFactory
{
    public static function create(array $filters, array $allowedFilters = []): ApiFilterCollection
    {
        //$filters = $this->getFilters($request);

        if (empty($filters)) {
            return new ApiFilterCollection();
        }

        $apiFilterCollection = ApiFilterCollection::fromArray($filters);

        if (empty($allowedFilters)) {
            return $apiFilterCollection;
        }

        /** @var ApiFilter $filter */
        foreach ($apiFilterCollection as $filter) {
            if (!isset($allowedFilters[$filter->getField()])) {
                throw new \InvalidArgumentException('Filter not valid: ' . $filter->getField());
            }

            // Allowed operations for controller
            $allowedOperations = $allowedFilters[$filter->getField()];

            if (!in_array($filter->getOperator(), $allowedOperations)) {
                throw new \InvalidArgumentException(
                    'Filter operator not valid "' . $filter->getOperator() . '" for field "' . $filter->getField() . '"'
                );
            }
        }

        return $apiFilterCollection;
    }
}