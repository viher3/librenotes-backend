<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

final class UnsupportedUserLocale extends DomainError
{
    public function __construct(private string $value)
    {
        parent::__construct('Unsupported locale: ' . $this->value);
    }

    public function errorCode(): string
    {
        return 'user.locale_unsupported';
    }

    /**
     * @return array<string, string>
     */
    public function params(): array
    {
        return [
            'value' => $this->value,
        ];
    }
}
