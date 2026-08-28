<?php

namespace App\Core\Domain\Specification;

abstract class AbstractSpecification
{
    abstract public function isSatisfiedBy(mixed $value): bool;
}