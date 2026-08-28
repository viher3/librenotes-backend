<?php

namespace App\Core\Domain\Filter;

use Ramsey\Collection\AbstractCollection;

/**
 * @extends AbstractCollection<DomainFilter>
 * @implements FilterCollection<DomainFilter>
 */
final class DomainFilterCollection extends AbstractCollection implements FilterCollection
{
    /**
     * @param array $filters
     * @return self
     */
    public static function fromArray(array $filters): self
    {
        $apiFilters = [];

        foreach ($filters as $filter) {
            $apiFilters[] = DomainFilter::fromArray($filter);
        }

        return new self($apiFilters);
    }

    public static function fromPrimitives(string $value): self
    {
        $filters = json_decode($value, true);

        return self::fromArray($filters ?: []);
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
     * @return array
     */
    public function serialize(): array
    {
        $response = [];

        /** @var Filter $apiFilter */
        foreach ($this->data as $apiFilter) {
            $response[] = $apiFilter->serialize();
        }

        return $response;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return DomainFilter::class;
    }
}
