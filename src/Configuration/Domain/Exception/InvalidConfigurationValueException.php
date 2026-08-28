<?php

declare(strict_types=1);

namespace App\Configuration\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

final class InvalidConfigurationValueException extends DomainError
{
    public function __construct(
        private readonly string $value,
        private readonly string $type
    ) {
        parent::__construct(sprintf('Value "%s" does not match declared type "%s"', $this->value, $this->type));
    }

    public function errorCode(): string
    {
        return 'configuration.invalid_value';
    }

    public function params(): array
    {
        return [
            'value' => $this->value,
            'type' => $this->type,
        ];
    }
}
