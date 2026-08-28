<?php

declare(strict_types=1);

namespace App\Configuration\Application\Update;

use App\Core\Application\Command\Command;

readonly class UpdateConfigurationCommand implements Command
{
    public function __construct(
        public string $key,
        public ?string $value,
        public ?string $type,
        public ?string $description,
        public ?bool $isSensitive,
        public string $changedBy
    ) {
    }
}
