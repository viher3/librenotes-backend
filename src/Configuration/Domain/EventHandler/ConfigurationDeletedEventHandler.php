<?php

declare(strict_types=1);

namespace App\Configuration\Domain\EventHandler;

use App\Configuration\Domain\Event\ConfigurationDeleted;
use App\Core\Domain\Bus\Event\EventHandler;

interface ConfigurationDeletedEventHandler extends EventHandler
{
    public function __invoke(ConfigurationDeleted $event): void;
}
