<?php

declare(strict_types=1);

namespace App\Configuration\Application\GetValue;

use App\Core\Application\Query\Query;

/**
 * Bus-only: no controller ever dispatches this. It is the one sanctioned door other
 * modules' Fetcher adapters use to read a configuration value in-process — see the
 * Fetcher pattern in the Configuration module design.
 */
readonly class GetConfigurationValueQuery implements Query
{
    public function __construct(
        public string $key
    ) {
    }
}
