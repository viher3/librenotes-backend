<?php

namespace App\Core\Domain\Exception;

class BadRequest extends DomainError
{
    private const MESSAGE = 'Request does not contain proper parameters';
    private array $params = [];

    public function __construct(
        string $message,
        string $property,
        ?string $pointer = null
    )
    {
        $error = [
            'message' => $message,
            'property' => $property
        ];
        if (!empty($pointer)) {
            $error['pointer'] = $pointer;
        }
        $this->params = $error;

        parent::__construct(self::MESSAGE . ': ' . json_encode($error));
    }

    public function errorCode(): string
    {
        return 'bad_request';
    }

    public function params(): array
    {
        return $this->params;
    }
}