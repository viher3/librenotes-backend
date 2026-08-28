<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Event;

use App\Core\Domain\Bus\Event\DomainEvent;

final class ConfigurationDeleted extends DomainEvent
{
    public function __construct(
        private readonly string $id,
        private readonly string $key,
        private readonly string $lastValue,
        private readonly ?int $lastKeyVersion,
        private readonly string $changedBy,
        string $eventId,
        string $occurredOn
    ) {
        parent::__construct($this->id, $occurredOn, $eventId, [
            'id' => $this->id,
            'key' => $this->key,
            'lastValue' => $this->lastValue,
            'lastKeyVersion' => $this->lastKeyVersion,
            'changedBy' => $this->changedBy,
        ]);
    }

    public static function eventName(): string
    {
        return 'ConfigurationDeleted';
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
            lastValue: $body['lastValue'],
            lastKeyVersion: $body['lastKeyVersion'],
            changedBy: $body['changedBy'],
            eventId: $eventId,
            occurredOn: $occurredOn,
        );
    }

    public function key(): string
    {
        return $this->key;
    }

    public function lastValue(): string
    {
        return $this->lastValue;
    }

    public function lastKeyVersion(): ?int
    {
        return $this->lastKeyVersion;
    }

    public function changedBy(): string
    {
        return $this->changedBy;
    }
}
