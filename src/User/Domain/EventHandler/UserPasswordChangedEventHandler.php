<?php

namespace App\User\Domain\EventHandler;

use App\Core\Domain\Bus\Event\EventHandler;
use App\User\Domain\Event\UserPasswordChanged;

interface UserPasswordChangedEventHandler extends EventHandler
{
    public function __invoke(UserPasswordChanged $event): void;
}
