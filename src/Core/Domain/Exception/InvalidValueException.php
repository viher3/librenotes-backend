<?php

namespace App\Core\Domain\Exception;

class InvalidValueException extends DomainError
{
    public function __construct(private string $value)
    {
        $message = sprintf('Invalid value provided: %s', $this->value);
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return 'invalid_value';
    }

    public function params(): array
    {
        return [
            'value' => $this->value
        ];
    }
}
