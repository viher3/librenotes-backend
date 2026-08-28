<?php

declare(strict_types=1);

namespace App\Notification\Domain\Event;

use App\Core\Domain\Bus\Event\DomainEvent;

final class NotificationFailed extends DomainEvent
{
    public function __construct(
        private readonly string $id,
        private readonly string $channel,
        private readonly string $recipient,
        private readonly int $attempts,
        private readonly string $error,
        string $eventId,
        string $occurredOn
    ) {
        parent::__construct($this->id, $occurredOn, $eventId, [
            'id' => $this->id,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'attempts' => $this->attempts,
            'error' => $this->error,
        ]);
    }

    public static function eventName(): string
    {
        return 'NotificationFailed';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return array_merge($this->body, [
            'eventId' => $this->eventId,
            'occurredOn' => $this->occurredOn,
        ]);
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function fromPrimitives(
        string $aggregateId,
        string $eventId,
        string $occurredOn,
        array $body = []
    ): DomainEvent {
        return new self(
            id: $aggregateId,
            channel: $body['channel'],
            recipient: $body['recipient'],
            attempts: $body['attempts'],
            error: $body['error'],
            eventId: $eventId,
            occurredOn: $occurredOn,
        );
    }

    public function channel(): string
    {
        return $this->channel;
    }

    public function recipient(): string
    {
        return $this->recipient;
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function error(): string
    {
        return $this->error;
    }
}
