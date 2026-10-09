<?php

declare(strict_types=1);

namespace App\Notes\Application\UpdateLink;

use App\Core\Application\Command\Command;

readonly class UpdateLinkCommand implements Command
{
    public function __construct(
        public string $id,
        /** @var array<string, mixed> */
        public array $fields,
        public string $ownerId
    ) {
    }
}
