<?php

declare(strict_types=1);

namespace App\Configuration\Application\Detail;

use App\Core\Application\Query\Query;

readonly class ConfigurationDetailQuery implements Query
{
    public function __construct(
        public string $key
    ) {
    }
}
