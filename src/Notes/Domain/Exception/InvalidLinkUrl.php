<?php

declare(strict_types=1);

namespace App\Notes\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class InvalidLinkUrl extends DomainError
{
    public function __construct(
        private string $value
    ) {
        parent::__construct('Invalid link URL (an absolute http or https URL is required): ' . mb_substr($this->value, 0, 100));
    }

    public function errorCode(): string
    {
        return 'link.invalid_url';
    }

    /**
     * @return array<string, string>
     */
    public function params(): array
    {
        return [
            'value' => mb_substr($this->value, 0, 100),
        ];
    }
}
