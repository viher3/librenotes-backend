<?php

declare(strict_types=1);

namespace App\Core\Domain\Exception;

use App\Core\Domain\Aggregate\AggregateRoot;

class InvalidPartialUpdateFieldsException extends DomainError
{
    public function __construct(
        private AggregateRoot $aggregateRoot,
        private array $fields
    )
    {
        parent::__construct(
            'Invalid update fields "' . implode(", ", $this->fields) . '" for ' . $this->aggregateRoot::class
        );
    }

    public function errorCode(): string
    {
        return 'invalid_update_fields';
    }

    public function params(): array
    {
        return [
            'aggregate' => $this->aggregateRoot::class,
            'fields' => $this->fields
        ];
    }
}
