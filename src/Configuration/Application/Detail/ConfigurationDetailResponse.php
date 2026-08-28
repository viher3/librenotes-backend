<?php

declare(strict_types=1);

namespace App\Configuration\Application\Detail;

use App\Configuration\Domain\Model\Configuration;
use App\Core\Application\Query\QueryResponse;

final class ConfigurationDetailResponse implements QueryResponse
{
    public function __construct(
        private readonly Configuration $configuration
    ) {
    }

    public function getResult(): array
    {
        $isSensitive = $this->configuration->isSensitive()->value();

        return [
            'id' => $this->configuration->getId()->value(),
            'key' => $this->configuration->getKey()->value(),
            'type' => $this->configuration->getType()->value(),
            'description' => $this->configuration->getDescription()?->value(),
            'is_sensitive' => $isSensitive,
            // Never returned in plaintext over HTTP once set — see etc/docs on Configuration encryption.
            'value' => $isSensitive ? null : $this->configuration->getValue()->value(),
            'created_at' => $this->configuration->getCreatedAt()->value()->format(DATE_ATOM),
            'updated_at' => $this->configuration->getUpdatedAt()->value()->format(DATE_ATOM),
        ];
    }
}
