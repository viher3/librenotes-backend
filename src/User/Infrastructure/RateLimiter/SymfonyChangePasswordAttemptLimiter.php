<?php

declare(strict_types=1);

namespace App\User\Infrastructure\RateLimiter;

use App\User\Domain\Exception\ChangePasswordRateLimited;
use App\User\Domain\Service\ChangePasswordAttemptLimiter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class SymfonyChangePasswordAttemptLimiter implements ChangePasswordAttemptLimiter
{
    public function __construct(
        #[Autowire(service: 'limiter.change_password_attempts')]
        private RateLimiterFactory $factory,
    ) {
    }

    public function assertAllowed(string $userId): void
    {
        // consume(0) inspects the window without spending a token (and is always "accepted").
        $rateLimit = $this->factory->create($userId)->consume(0);

        if ($rateLimit->getRemainingTokens() < 1) {
            throw new ChangePasswordRateLimited(
                max(1, $rateLimit->getRetryAfter()->getTimestamp() - time())
            );
        }
    }

    public function registerFailure(string $userId): void
    {
        $this->factory->create($userId)->consume(1);
    }

    public function reset(string $userId): void
    {
        $this->factory->create($userId)->reset();
    }
}
