<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Exception;

use App\Core\Domain\Exception\NotFoundException;

final class ConfigurationNotFoundException extends NotFoundException
{
    public function __construct(private readonly string $key)
    {
        parent::__construct($this->key);
    }

    public function errorMessage(): string
    {
        return 'Configuration not found: ' . $this->key;
    }

    public function errorCode(): string
    {
        return 'configuration.not_found';
    }
}
