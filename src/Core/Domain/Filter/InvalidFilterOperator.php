<?php

namespace App\Core\Domain\Filter;

use App\Core\Domain\Exception\DomainError;

class InvalidFilterOperator extends DomainError
{
    public function __construct(
        private string $operator,
        private string $field,
    )
    {
        parent::__construct(
            'Invalid filter operator: "' . $this->operator . '". for field ' . $this->field
        );
    }

    public function errorCode(): string
    {
        return 'filter.invalid_operator';
    }

    public function params(): array
    {
        return [
            'operator' => $this->operator,
            'field' => $this->field
        ];
    }
}
