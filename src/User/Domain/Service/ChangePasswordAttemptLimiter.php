<?php

declare(strict_types=1);

namespace App\User\Domain\Service;

use App\User\Domain\Exception\ChangePasswordRateLimited;

/**
 * Throttles wrong "current password" attempts per user: only failures count and a success clears them.
 */
interface ChangePasswordAttemptLimiter
{
    /**
     * @throws ChangePasswordRateLimited when the failed attempts allowance is exhausted
     */
    public function assertAllowed(string $userId): void;

    public function registerFailure(string $userId): void;

    public function reset(string $userId): void;
}
