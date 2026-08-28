<?php

namespace App\Core\Domain\Filter;

interface Filter
{
    /**
     * @param string $field
     * @param string $value
     * @param string $operator
     */
    public function __construct(string $field, string $value, string $operator);

    /**
     * @return string
     */
    public function getField(): string;

    /**
     * @return mixed
     */
    public function getValue(): mixed;

    /**
     * @return string
     */
    public function getOperator(): string;

    /**
     * @return array
     */
    public function serialize(): array;
}
