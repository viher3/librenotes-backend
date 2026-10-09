<?php

declare(strict_types=1);

namespace App\Notes\Domain\Exception;

use App\Core\Domain\Exception\NotFoundException;

class LinkNotFound extends NotFoundException
{
    public function __construct(
        private string $value
    ) {
        parent::__construct($this->value);
    }

    public function errorMessage(): string
    {
        return 'Link not found: ' . $this->value;
    }

    public function errorCode(): string
    {
        return 'link.not_found';
    }
}
