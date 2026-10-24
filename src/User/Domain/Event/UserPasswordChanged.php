<?php

namespace App\User\Domain\Event;

use App\Core\Domain\Bus\Event\DomainEvent;

final class UserPasswordChanged extends DomainEvent
{
    public function __construct(
        private readonly string $id,
        private readonly string $email,
        private readonly string $locale,
        string                  $eventId,
        string                  $occurredOn
    )
    {
        parent::__construct($this->id, $occurredOn, $eventId, [
            'id' => $this->id,
            'email' => $this->email,
            'locale' => $this->locale,
        ]);
    }

    public static function eventName(): string
    {
        return 'UserPasswordChanged';
    }

    public function email(): string
    {
        return $this->email;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPrimitives(): array
    {
        return array_merge($this->body, [
            'eventId' => $this->eventId,
            'occurredOn' => $this->occurredOn
        ]);
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function fromPrimitives(
        string $aggregateId,
        string $eventId,
        string $occurredOn,
        array  $body = []
    ): DomainEvent
    {
        return new self(
            id: $aggregateId,
            email: $body['email'],
            locale: $body['locale'],
            eventId: $body['eventId'],
            occurredOn: $occurredOn,
        );
    }
}
