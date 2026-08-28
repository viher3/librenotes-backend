<?php

namespace App\Core\Domain\Time;

interface DateTimeProvider
{
    public function now(): \DateTimeInterface;
}
