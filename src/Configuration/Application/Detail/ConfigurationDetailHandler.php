<?php

declare(strict_types=1);

namespace App\Configuration\Application\Detail;

use App\Configuration\Domain\Exception\ConfigurationNotFoundException;
use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Repository\ConfigurationRepository;
use App\Core\Application\Query\QueryHandler;

final class ConfigurationDetailHandler implements QueryHandler
{
    public function __construct(
        private readonly ConfigurationRepository $configurationRepository
    ) {
    }

    public function __invoke(ConfigurationDetailQuery $query): ConfigurationDetailResponse
    {
        $configuration = $this->configurationRepository->findByKey(new ConfigurationKey($query->key));

        if (!$configuration) {
            throw new ConfigurationNotFoundException($query->key);
        }

        return new ConfigurationDetailResponse($configuration);
    }
}
