<?php

declare(strict_types=1);

namespace App\Configuration\Application\History;

use App\Configuration\Domain\Model\ValueObject\ConfigurationKey;
use App\Configuration\Domain\Repository\ConfigurationHistoryRepository;
use App\Core\Application\Query\QueryHandler;

/**
 * Deliberately never checks whether the configuration still exists: the ledger's
 * whole purpose is staying readable after the live row is deleted (see
 * configuration_key denormalization). An unknown key just returns an empty page,
 * same as Search — not a 404.
 */
final class ConfigurationHistoryHandler implements QueryHandler
{
    public function __construct(
        private readonly ConfigurationHistoryRepository $historyRepository
    ) {
    }

    public function __invoke(ConfigurationHistoryQuery $query): ConfigurationHistoryResponse
    {
        $key = new ConfigurationKey($query->key);

        $entries = $this->historyRepository->findByConfigurationKey($key, $query->page, $query->perPage);
        $total = $this->historyRepository->countByConfigurationKey($key);

        return new ConfigurationHistoryResponse($entries, $total, $query->page, $query->perPage);
    }
}
