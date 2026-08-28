<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Time;

use App\Core\Domain\Time\DateTimeProvider;

class SystemDateTimeProvider implements DateTimeProvider
{
    public function now(): \DateTimeInterface
    {
        return new \DateTime();
    }
}
