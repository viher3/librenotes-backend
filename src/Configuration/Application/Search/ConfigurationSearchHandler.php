<?php

declare(strict_types=1);

namespace App\Configuration\Application\Search;

use App\Configuration\Domain\Repository\ConfigurationRepository;
use App\Core\Application\Query\QueryHandler;

final class ConfigurationSearchHandler implements QueryHandler
{
    public function __construct(
        private readonly ConfigurationRepository $configurationRepository
    ) {
    }

    public function __invoke(ConfigurationSearchQuery $query): ConfigurationSearchResponse
    {
        $configurations = $this->configurationRepository->search(
            ...$query->getAll()
        );

        $total = $this->configurationRepository->searchCount(
            $query->getFilters()
        );

        return new ConfigurationSearchResponse(
            $configurations,
            $total,
            $query->getPage(),
            $query->getItemsPerPage()
        );
    }
}
