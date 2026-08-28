<?php

namespace App\User\Infrastructure\EventHandler;

use App\User\Domain\Event\UserCreated;
use App\User\Domain\EventHandler\UserCreatedEventHandler;

class SymfonyUserCreatedEventHandler implements UserCreatedEventHandler
{
    public function __invoke(UserCreated $event): void
    {
        // TODO: Implement __invoke() method.
    }
}