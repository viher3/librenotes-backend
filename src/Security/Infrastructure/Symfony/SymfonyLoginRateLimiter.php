<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony;

use App\Security\Domain\Exception\TooManyAttemptsException;
use App\Security\Domain\Service\LoginRateLimiter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class SymfonyLoginRateLimiter implements LoginRateLimiter
{
    public function __construct(
        #[Autowire(service: 'limiter.login_attempts')]
        private RateLimiterFactory $loginAttemptsLimiterFactory,
        #[Autowire(service: 'limiter.token_renew_attempts')]
        private RateLimiterFactory $tokenRenewAttemptsLimiterFactory,
    ) {
    }

    public function consumeLoginAttempt(string $key): void
    {
        $this->consume($this->loginAttemptsLimiterFactory, $key);
    }

    public function consumeRenewAttempt(string $key): void
    {
        $this->consume($this->tokenRenewAttemptsLimiterFactory, $key);
    }

    private function consume(RateLimiterFactory $factory, string $key): void
    {
        if (!$factory->create($key)->consume(1)->isAccepted()) {
            throw new TooManyAttemptsException();
        }
    }
}
