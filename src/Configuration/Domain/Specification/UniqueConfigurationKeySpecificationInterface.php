<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Specification;

use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;

interface UniqueConfigurationKeySpecificationInterface
{
    public function isUnique(ConfigurationKey $key): bool;
}
