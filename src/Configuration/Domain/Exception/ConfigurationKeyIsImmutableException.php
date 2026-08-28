<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

final class ConfigurationKeyIsImmutableException extends DomainError
{
    public function __construct(private readonly string $key)
    {
        parent::__construct('Configuration key cannot be changed once created: ' . $this->key);
    }

    public function errorCode(): string
    {
        return 'configuration.key_is_immutable';
    }

    public function params(): array
    {
        return [
            'key' => $this->key,
        ];
    }
}
