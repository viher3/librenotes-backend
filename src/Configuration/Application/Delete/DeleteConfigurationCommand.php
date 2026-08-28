<?php

declare(strict_types=1);

namespace App\Configuration\Application\Delete;

use App\Core\Application\Command\Command;

readonly class DeleteConfigurationCommand implements Command
{
    public function __construct(
        public string $key,
        public string $changedBy
    ) {
    }
}
