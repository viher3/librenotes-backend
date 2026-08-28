<?php

declare(strict_types=1);

namespace App\Notification\Domain\Event;

use App\Core\Domain\Bus\Event\DomainEvent;

final class NotificationSent extends DomainEvent
{
    public function __construct(
        private readonly string $id,
        private readonly string $channel,
        private readonly string $recipient,
        private readonly ?string $providerMessageId,
        string $eventId,
        string $occurredOn
    ) {
        parent::__construct($this->id, $occurredOn, $eventId, [
            'id' => $this->id,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'providerMessageId' => $this->providerMessageId,
        ]);
    }

    public static function eventName(): string
    {
        return 'NotificationSent';
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
            providerMessageId: $body['providerMessageId'],
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

    public function providerMessageId(): ?string
    {
        return $this->providerMessageId;
    }
}
