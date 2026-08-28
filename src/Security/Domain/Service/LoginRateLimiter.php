<?php

declare(strict_types=1);

namespace App\Security\Domain\Service;

use App\Security\Domain\Exception\TooManyAttemptsException;

interface LoginRateLimiter
{
    /**
     * @throws TooManyAttemptsException
     */
    public function consumeLoginAttempt(string $key): void;

    /**
     * @throws TooManyAttemptsException
     */
    public function consumeRenewAttempt(string $key): void;
}
