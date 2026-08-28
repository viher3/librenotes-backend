<?php

declare(strict_types=1);

namespace App\Configuration\Application\Create;

use App\Core\Application\Command\Command;

readonly class CreateConfigurationCommand implements Command
{
    public function __construct(
        public string $id,
        public string $key,
        public string $value,
        public string $type,
        public ?string $description,
        public bool $isSensitive,
        public string $changedBy
    ) {
    }
}
