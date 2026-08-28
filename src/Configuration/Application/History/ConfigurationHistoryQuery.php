<?php

declare(strict_types=1);

namespace App\Configuration\Application\History;

use App\Core\Application\Query\Query;

readonly class ConfigurationHistoryQuery implements Query
{
    public function __construct(
        public string $key,
        public int $page = 1,
        public int $perPage = 20
    ) {
    }
}
