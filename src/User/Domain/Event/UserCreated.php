<?php

namespace App\User\Domain\Event;

use App\Core\Domain\Bus\Event\DomainEvent;

final class UserCreated extends DomainEvent
{
    public function __construct(
        private readonly string $id,
        private readonly string $username,
        private readonly string $email,
        private readonly bool   $active,
        private readonly bool   $emailValidated,
        string                  $eventId,
        string                  $occurredOn
    )
    {
        parent::__construct($this->id, $eventId, $occurredOn, [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'active' => $this->active,
            'emailValidated' => $this->emailValidated,
        ]);
    }

    public static function eventName(): string
    {
        return 'UserCreated';
    }

    public function toPrimitives(): array
    {
        return array_merge($this->body, [
            'eventId' => $this->eventId,
            'occurredOn' => $this->occurredOn
        ]);
    }

    public static function fromPrimitives(
        string $aggregateId,
        string $eventId,
        string $occurredOn,
        array  $body = []
    ): DomainEvent
    {
        return new self(
            id: $aggregateId,
            username: $body['username'],
            email: $body['email'],
            active: $body['active'],
            emailValidated: $body['emailValidated'],
            eventId: $body['eventId'],
            occurredOn: $occurredOn,
        );
    }
}