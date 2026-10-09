<?php

declare(strict_types=1);

namespace App\Notes\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class SearchQueryTooShort extends DomainError
{
    public const int MIN_LENGTH = 2;

    public function __construct()
    {
        parent::__construct('The search query must have at least ' . self::MIN_LENGTH . ' characters');
    }

    public function errorCode(): string
    {
        return 'search.query_too_short';
    }

    /**
     * @return array<string, int>
     */
    public function params(): array
    {
        return [
            'min_length' => self::MIN_LENGTH,
        ];
    }
}
