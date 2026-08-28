<?php

namespace App\Core\Domain\Bus\Event;

interface EventBus
{
    /**
     * @param DomainEvent ...$events
     * @return void
     */
    public function publish(DomainEvent ...$events): void;
}
