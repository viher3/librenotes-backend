<?php

namespace App\User\Domain\EventHandler;

use App\Core\Domain\Bus\Event\EventHandler;
use App\User\Domain\Event\UserCreated;

interface UserCreatedEventHandler extends EventHandler
{
    public function __invoke(UserCreated $event): void;
}