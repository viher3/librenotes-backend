<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Event;

use App\Core\Domain\Bus\Event\DomainEvent;

final class ConfigurationValueChanged extends DomainEvent
{
    public function __construct(
        private readonly string $id,
        private readonly string $key,
        private readonly string $previousValue,
        private readonly ?int $previousKeyVersion,
        private readonly string $newValue,
        private readonly string $type,
        private readonly ?string $description,
        private readonly bool $isSensitive,
        private readonly ?int $newKeyVersion,
        private readonly string $changedBy,
        string $eventId,
        string $occurredOn
    ) {
        parent::__construct($this->id, $occurredOn, $eventId, [
            'id' => $this->id,
            'key' => $this->key,
            'previousValue' => $this->previousValue,
            'previousKeyVersion' => $this->previousKeyVersion,
            'newValue' => $this->newValue,
            'type' => $this->type,
            'description' => $this->description,
            'isSensitive' => $this->isSensitive,
            'newKeyVersion' => $this->newKeyVersion,
            'changedBy' => $this->changedBy,
        ]);
    }

    public static function eventName(): string
    {
        return 'ConfigurationValueChanged';
    }

    public function toPrimitives(): array
    {
        return array_merge($this->body, [
            'eventId' => $this->eventId,
            'occurredOn' => $this->occurredOn,
        ]);
    }

    public static function fromPrimitives(
        string $aggregateId,
        string $eventId,
        string $occurredOn,
        array $body = []
    ): DomainEvent {
        return new self(
            id: $aggregateId,
            key: $body['key'],
            previousValue: $body['previousValue'],
            previousKeyVersion: $body['previousKeyVersion'],
            newValue: $body['newValue'],
            type: $body['type'],
            description: $body['description'],
            isSensitive: $body['isSensitive'],
            newKeyVersion: $body['newKeyVersion'],
            changedBy: $body['changedBy'],
            eventId: $eventId,
            occurredOn: $occurredOn,
        );
    }

    public function key(): string
    {
        return $this->key;
    }

    public function previousValue(): string
    {
        return $this->previousValue;
    }

    public function previousKeyVersion(): ?int
    {
        return $this->previousKeyVersion;
    }

    public function newValue(): string
    {
        return $this->newValue;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function isSensitive(): bool
    {
        return $this->isSensitive;
    }

    public function newKeyVersion(): ?int
    {
        return $this->newKeyVersion;
    }

    public function changedBy(): string
    {
        return $this->changedBy;
    }
}
