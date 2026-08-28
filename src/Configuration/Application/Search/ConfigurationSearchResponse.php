<?php

declare(strict_types=1);

namespace App\Configuration\Application\Search;

use App\Configuration\Domain\Model\Configuration;
use App\Core\Application\Query\SearchResponse;

final class ConfigurationSearchResponse implements SearchResponse
{
    /**
     * @param Configuration[] $configurations
     */
    public function __construct(
        private readonly array $configurations,
        private readonly int $total,
        private readonly int $page,
        private readonly int $perPage
    ) {
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getResult(): array
    {
        $data = [];

        foreach ($this->configurations as $configuration) {
            $isSensitive = $configuration->isSensitive()->value();

            $data[] = [
                'id' => $configuration->getId()->value(),
                'key' => $configuration->getKey()->value(),
                'type' => $configuration->getType()->value(),
                'description' => $configuration->getDescription()?->value(),
                'is_sensitive' => $isSensitive,
                'value' => $isSensitive ? null : $configuration->getValue()->value(),
                'created_at' => $configuration->getCreatedAt()->value()->format(DATE_ATOM),
                'updated_at' => $configuration->getUpdatedAt()->value()->format(DATE_ATOM),
            ];
        }

        return [
            'meta' => [
                'page' => $this->page,
                'size' => $this->perPage,
                'total' => $this->total,
                'last_page' => $this->perPage > 0 ? (int) ceil($this->total / $this->perPage) : 0,
            ],
            'data' => $data,
        ];
    }
}
