<?php

declare(strict_types=1);

namespace App\Notification\Domain\Event;

use App\Core\Domain\Bus\Event\DomainEvent;

final class NotificationQueued extends DomainEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly string $id,
        private readonly string $channel,
        private readonly string $templateKey,
        private readonly string $locale,
        private readonly string $recipient,
        private readonly ?string $recipientId,
        private readonly array $payload,
        string $eventId,
        string $occurredOn
    ) {
        parent::__construct($this->id, $occurredOn, $eventId, [
            'id' => $this->id,
            'channel' => $this->channel,
            'templateKey' => $this->templateKey,
            'locale' => $this->locale,
            'recipient' => $this->recipient,
            'recipientId' => $this->recipientId,
            'payload' => $this->payload,
        ]);
    }

    public static function eventName(): string
    {
        return 'NotificationQueued';
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
            templateKey: $body['templateKey'],
            locale: $body['locale'],
            recipient: $body['recipient'],
            recipientId: $body['recipientId'],
            payload: $body['payload'],
            eventId: $eventId,
            occurredOn: $occurredOn,
        );
    }

    public function channel(): string
    {
        return $this->channel;
    }

    public function templateKey(): string
    {
        return $this->templateKey;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function recipient(): string
    {
        return $this->recipient;
    }

    public function recipientId(): ?string
    {
        return $this->recipientId;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }
}
