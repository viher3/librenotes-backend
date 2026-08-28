<?php

namespace App\Core\Domain\Exception;

class NotFoundException extends DomainError
{
    public function __construct(
        private string $value = ''
    ){
        parent::__construct($this->errorMessage());
    }

    public function errorCode(): string
    {
        return 'not_found';
    }

    public function errorMessage(): string
    {
        if($this->value){
            return 'Not found: ' . $this->value;
        }

        return 'Not found';
    }

    public function params(): array
    {
        return [
            'value' => $this->value
        ];
    }
}
