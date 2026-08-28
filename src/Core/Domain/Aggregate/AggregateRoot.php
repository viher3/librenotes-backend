<?php

namespace App\Core\Domain\Aggregate;

use App\Core\Domain\Bus\Event\DomainEvent;
use App\Core\Domain\Exception\InvalidPartialUpdateFieldsException;

abstract class AggregateRoot
{
    private array $domainEvents = [];

    final public function pullDomainEvents(): array
    {
        $domainEvents = $this->domainEvents;
        $this->domainEvents = [];

        return $domainEvents;
    }

    final protected function record(DomainEvent $domainEvent): void
    {
        $this->domainEvents[] = $domainEvent;
    }

    public function partialUpdate(
        array $validFields,
        array $fields
    ) : void
    {
        $this->assertValidPartialUpdateFields($validFields, $fields);

        foreach($fields as $field => $value){
            $this->$field = $this->castPartialUpdateValue($field, $value);
        }
    }

    protected function castPartialUpdateValue(string $field, mixed $value): mixed
    {
        return $value;
    }

    protected function assertValidPartialUpdateFields(
        array $validFields,
        array $fields
    ): void
    {
        $invalidFields = [];

        foreach($fields as $field => $value){
            if(!in_array($field, $validFields)){
                $invalidFields[] = $field;
            }
        }

        if($invalidFields){
            throw new InvalidPartialUpdateFieldsException($this, $invalidFields);
        }
    }
}