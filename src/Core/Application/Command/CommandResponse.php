<?php

declare(strict_types=1);

namespace App\Core\Application\Command;

interface CommandResponse
{
    public function getResult(): array;
}