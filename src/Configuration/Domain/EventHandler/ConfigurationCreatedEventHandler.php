<?php

declare(strict_types=1);

namespace App\Configuration\Domain\EventHandler;

use App\Configuration\Domain\Event\ConfigurationCreated;
use App\Core\Domain\Bus\Event\EventHandler;

interface ConfigurationCreatedEventHandler extends EventHandler
{
    public function __invoke(ConfigurationCreated $event): void;
}
