<?php

declare(strict_types=1);

namespace App\Configuration\Domain\EventHandler;

use App\Configuration\Domain\Event\ConfigurationValueChanged;
use App\Core\Domain\Bus\Event\EventHandler;

interface ConfigurationValueChangedEventHandler extends EventHandler
{
    public function __invoke(ConfigurationValueChanged $event): void;
}
