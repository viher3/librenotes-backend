<?php

namespace App\Core\Domain\Exception;

class InvalidArgumentException extends DomainError
{
    public function __construct(
        private string $argument
    )
    {
        parent::__construct('Invalid argument: "' . $this->argument . '".');
    }

    public function errorCode(): string
    {
        return 'invalid_argument';
    }

    public function params(): array
    {
        return [
            'argument' => $this->argument,
        ];
    }
}
