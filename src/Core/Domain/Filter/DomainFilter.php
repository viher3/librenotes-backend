<?php

namespace App\Core\Domain\Filter;

class DomainFilter implements Filter
{
    public function __construct(
        private string $field,
        private string  $value,
        private string $operator
    )
    {
        if (!in_array($this->operator, FilterOperator::AVAILABLES)) {
            throw new InvalidFilterOperator($this->operator, $this->field);
        }
    }

    public static function fromArray(array $filter) : self
    {
        return new self(
            field: $filter['f'],
            value: $filter['v'],
            operator: $filter['o']
        );
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function serialize(): array
    {
        return [
            'f' => $this->field,
            'v' => $this->value,
            'o' => $this->operator
        ];
    }
}
