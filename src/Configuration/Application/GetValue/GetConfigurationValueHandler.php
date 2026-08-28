<?php

declare(strict_types=1);

namespace App\Configuration\Application\GetValue;

use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Repository\ConfigurationRepository;
use App\Configuration\Domain\Service\ConfigurationValueCaster;
use App\Configuration\Domain\Service\ConfigurationValueEncryptor;
use App\Configuration\Infrastructure\Cache\ConfigurationValueCache;
use App\Core\Application\Query\QueryHandler;

final class GetConfigurationValueHandler implements QueryHandler
{
    public function __construct(
        private readonly ConfigurationRepository $configurationRepository,
        private readonly ConfigurationValueEncryptor $encryptor,
        private readonly ConfigurationValueCaster $caster,
        private readonly ConfigurationValueCache $cache
    ) {
    }

    public function __invoke(GetConfigurationValueQuery $query): GetConfigurationValueResponse
    {
        return $this->cache->get(
            $query->key,
            function () use ($query): GetConfigurationValueResponse {
                $configuration = $this->configurationRepository->findByKey(new ConfigurationKey($query->key));

                if (!$configuration) {
                    return new GetConfigurationValueResponse(found: false);
                }

                $rawValue = $configuration->isSensitive()->value()
                    ? $this->encryptor->decrypt($configuration->getValue()->value(), $configuration->getKeyVersion())
                    : $configuration->getValue()->value();

                return new GetConfigurationValueResponse(
                    found: true,
                    value: $this->caster->cast($rawValue, $configuration->getType()),
                    type: $configuration->getType()->value(),
                );
            }
        );
    }
}
