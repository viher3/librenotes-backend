<?php

namespace App\Core\Infrastructure\Bus\Event;

use App\Core\Domain\Bus\Event\DomainEvent;
use App\Core\Domain\Bus\Event\EventBus;
use App\Core\Infrastructure\Bus\HandlerFailedExceptionUnwrapper;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

final class SymfonyEventBus implements EventBus
{
    /**
     * @param MessageBusInterface $eventBus
     */
    public function __construct(
        private MessageBusInterface $eventBus
    ) {
    }

    /**
     * @param DomainEvent ...$events
     * @return void
     */
    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            try {
                $this->eventBus->dispatch($event);
            } catch (HandlerFailedException $e) {
                throw HandlerFailedExceptionUnwrapper::unwrap($e);
            }
        }
    }
}
