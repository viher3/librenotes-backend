<?php

namespace App\Core\Infrastructure\Filter;

use App\Core\Domain\Filter\Filter;
use App\Core\Domain\Filter\FilterCollection;
use Ramsey\Collection\AbstractCollection;

/**
 * @extends AbstractCollection<ApiFilter>
 * @implements FilterCollection<ApiFilter>
 */
final class ApiFilterCollection extends AbstractCollection implements FilterCollection
{
    /**
     * @param array $filters
     * @return self
     */
    public static function fromArray(array $filters): self
    {
        $apiFilters = [];

        foreach ($filters as $filter) {
            $apiFilters[] = new ApiFilter(
                $filter['field'],
                $filter['value'],
                $filter['operator']
            );
        }

        return new self($apiFilters);
    }

    public static function fromPrimitives(string $value): self
    {
        $filters = json_decode($value, true);

        return self::fromArray($filters ?: []);
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        $response = [];

        /** @var ApiFilter $apiFilter */
        foreach ($this->data as $apiFilter) {
            $response[] = $apiFilter->serialize();
        }

        return $response;
    }

    /**
     * @param string $field
     * @return Filter|null
     */
    public function findByField(string $field) : ?Filter
    {
        /** @var Filter $filter */
        foreach($this->data as $filter){
            if($filter->getField() === $field){
                return $filter;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return ApiFilter::class;
    }
}