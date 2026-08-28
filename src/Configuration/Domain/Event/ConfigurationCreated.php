<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Event;

use App\Core\Domain\Bus\Event\DomainEvent;

final class ConfigurationCreated extends DomainEvent
{
    public function __construct(
        private readonly string $id,
        private readonly string $key,
        private readonly string $value,
        private readonly string $type,
        private readonly ?string $description,
        private readonly bool $isSensitive,
        private readonly ?int $keyVersion,
        private readonly string $changedBy,
        string $eventId,
        string $occurredOn
    ) {
        parent::__construct($this->id, $occurredOn, $eventId, [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $this->value,
            'type' => $this->type,
            'description' => $this->description,
            'isSensitive' => $this->isSensitive,
            'keyVersion' => $this->keyVersion,
            'changedBy' => $this->changedBy,
        ]);
    }

    public static function eventName(): string
    {
        return 'ConfigurationCreated';
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
            value: $body['value'],
            type: $body['type'],
            description: $body['description'],
            isSensitive: $body['isSensitive'],
            keyVersion: $body['keyVersion'],
            changedBy: $body['changedBy'],
            eventId: $eventId,
            occurredOn: $occurredOn,
        );
    }

    public function key(): string
    {
        return $this->key;
    }

    public function value(): string
    {
        return $this->value;
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

    public function keyVersion(): ?int
    {
        return $this->keyVersion;
    }

    public function changedBy(): string
    {
        return $this->changedBy;
    }
}
