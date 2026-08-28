<?php

namespace App\User\Domain\Exception;

use App\Core\Domain\Exception\NotFoundException;

class NotFoundUser extends NotFoundException
{
    public function __construct(
        private string $value
    )
    {
        parent::__construct($this->value);
    }

    public function errorMessage() : string
    {
        return 'User not found: ' . $this->value;
    }

    public function errorCode(): string
    {
        return 'user.not_found';
    }
}
