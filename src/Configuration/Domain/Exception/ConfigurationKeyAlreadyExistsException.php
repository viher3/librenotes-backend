<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

final class ConfigurationKeyAlreadyExistsException extends DomainError
{
    public function __construct(private readonly string $key)
    {
        parent::__construct('Configuration key already exists: ' . $this->key);
    }

    public function errorCode(): string
    {
        return 'configuration.key_already_exists';
    }

    public function params(): array
    {
        return [
            'key' => $this->key,
        ];
    }
}
