<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\RateLimiter;

use App\User\Domain\Exception\ChangePasswordRateLimited;
use App\User\Infrastructure\RateLimiter\SymfonyChangePasswordAttemptLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

class SymfonyChangePasswordAttemptLimiterTest extends TestCase
{
    private SymfonyChangePasswordAttemptLimiter $limiter;

    protected function setUp(): void
    {
        $this->limiter = new SymfonyChangePasswordAttemptLimiter(
            new RateLimiterFactory(
                ['id' => 'change_password_attempts', 'policy' => 'sliding_window', 'limit' => 3, 'interval' => '15 minutes'],
                new InMemoryStorage()
            )
        );
    }

    public function testAllowsUntilThreeFailuresThenLocks(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->limiter->assertAllowed('user-1');
            $this->limiter->registerFailure('user-1');
        }

        try {
            $this->limiter->assertAllowed('user-1');
            $this->fail('Expected ChangePasswordRateLimited');
        } catch (ChangePasswordRateLimited $e) {
            $this->assertGreaterThan(0, $e->retryAfter());
            $this->assertLessThanOrEqual(900, $e->retryAfter());
        }
    }

    public function testCheckingDoesNotSpendAttempts(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->limiter->assertAllowed('user-1');
        }

        $this->addToAssertionCount(1);
    }

    public function testResetClearsTheFailures(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->limiter->registerFailure('user-1');
        }

        $this->limiter->reset('user-1');
        $this->limiter->assertAllowed('user-1');

        $this->addToAssertionCount(1);
    }

    public function testUsersAreIndependent(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->limiter->registerFailure('user-1');
        }

        $this->limiter->assertAllowed('user-2');

        $this->addToAssertionCount(1);
    }
}
