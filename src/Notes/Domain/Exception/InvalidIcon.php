<?php

declare(strict_types=1);

namespace App\Notes\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class InvalidIcon extends DomainError
{
    public function __construct(
        private string $reason
    ) {
        parent::__construct('Invalid icon: ' . $this->reason);
    }

    public function errorCode(): string
    {
        return 'icon.invalid';
    }

    /**
     * @return array<string, string>
     */
    public function params(): array
    {
        return [
            'reason' => $this->reason,
        ];
    }
}
