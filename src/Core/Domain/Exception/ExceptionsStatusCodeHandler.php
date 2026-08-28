<?php

namespace App\Core\Domain\Exception;

interface ExceptionsStatusCodeHandler
{
    public function register(string $exceptionClass, int $statusCode): void;

    public function resolve(string $exceptionClass): ?int;
}