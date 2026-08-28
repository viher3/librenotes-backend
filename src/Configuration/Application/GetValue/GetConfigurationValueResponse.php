<?php

declare(strict_types=1);

namespace App\Configuration\Application\GetValue;

use App\Core\Application\Query\QueryResponse;

final readonly class GetConfigurationValueResponse implements QueryResponse
{
    public function __construct(
        private bool $found,
        private string|int|bool|array|null $value = null,
        private ?string $type = null
    ) {
    }

    public function found(): bool
    {
        return $this->found;
    }

    public function value(): string|int|bool|array|null
    {
        return $this->value;
    }

    public function type(): ?string
    {
        return $this->type;
    }

    public function getResult(): array
    {
        return [
            'found' => $this->found,
            'value' => $this->value,
            'type' => $this->type,
        ];
    }
}
