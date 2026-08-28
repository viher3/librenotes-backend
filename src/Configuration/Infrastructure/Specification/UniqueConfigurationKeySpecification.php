<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Specification;

use App\Configuration\Domain\Exception\ConfigurationKeyAlreadyExistsException;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Repository\ConfigurationRepository;
use App\Configuration\Domain\Specification\UniqueConfigurationKeySpecificationInterface;
use App\Core\Domain\Specification\AbstractSpecification;

final class UniqueConfigurationKeySpecification extends AbstractSpecification implements UniqueConfigurationKeySpecificationInterface
{
    public function __construct(
        private readonly ConfigurationRepository $configurationRepository
    ) {
    }

    public function isSatisfiedBy(mixed $value): bool
    {
        if ($this->configurationRepository->existsByKey($value)) {
            throw new ConfigurationKeyAlreadyExistsException($value->value());
        }

        return true;
    }

    public function isUnique(ConfigurationKey $key): bool
    {
        return $this->isSatisfiedBy($key);
    }
}
