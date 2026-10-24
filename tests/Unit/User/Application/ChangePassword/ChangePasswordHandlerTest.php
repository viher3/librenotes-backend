<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\ChangePassword;

use App\Core\Domain\Bus\Event\EventBus;
use App\Security\Domain\Model\RefreshToken;
use App\Security\Domain\Repository\RefreshTokenRepository;
use App\User\Application\ChangePassword\ChangePasswordCommand;
use App\User\Application\ChangePassword\ChangePasswordHandler;
use App\User\Domain\Exception\ChangePasswordRateLimited;
use App\User\Domain\Exception\InvalidNewPassword;
use App\User\Domain\Exception\InvalidUserPassword;
use App\User\Domain\Exception\NotFoundUser;
use App\User\Domain\Exception\PasswordUnchanged;
use App\User\Domain\Model\User;
use App\User\Domain\Service\ChangePasswordAttemptLimiter;
use App\User\Domain\Service\ChangePasswordService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ChangePasswordHandlerTest extends TestCase
{
    private ChangePasswordService&MockObject $changePasswordService;
    private ChangePasswordAttemptLimiter&MockObject $attemptLimiter;
    private RefreshTokenRepository&MockObject $refreshTokenRepository;
    private EventBus&MockObject $eventBus;
    private LoggerInterface&MockObject $logger;
    private ChangePasswordHandler $handler;

    protected function setUp(): void
    {
        $this->changePasswordService = $this->createMock(ChangePasswordService::class);
        $this->attemptLimiter = $this->createMock(ChangePasswordAttemptLimiter::class);
        $this->refreshTokenRepository = $this->createMock(RefreshTokenRepository::class);
        $this->eventBus = $this->createMock(EventBus::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new ChangePasswordHandler(
            $this->changePasswordService,
            $this->attemptLimiter,
            $this->refreshTokenRepository,
            $this->eventBus,
            $this->logger
        );
    }

    private function command(): ChangePasswordCommand
    {
        return new ChangePasswordCommand(
            userId: 'user-id',
            currentPassword: 'current-password',
            newPassword: 'new-password-1'
        );
    }

    public function testChangesThePasswordResetsTheLimiterRevokesRefreshTokensAndPublishesEvents(): void
    {
        $user = $this->createMock(User::class);
        $refreshToken = $this->createMock(RefreshToken::class);

        $this->changePasswordService
            ->expects($this->once())
            ->method('changePassword')
            ->with('user-id', 'new-password-1', 'current-password')
            ->willReturn($user);
        $this->attemptLimiter->expects($this->once())->method('assertAllowed')->with('user-id');
        $this->attemptLimiter->expects($this->once())->method('reset')->with('user-id');
        $this->attemptLimiter->expects($this->never())->method('registerFailure');
        $this->refreshTokenRepository->method('findActiveByUserId')->with('user-id')->willReturn([$refreshToken]);
        $refreshToken->expects($this->once())->method('revoke');
        $this->refreshTokenRepository->expects($this->once())->method('save')->with($refreshToken);
        $this->eventBus->expects($this->once())->method('publish');

        ($this->handler)($this->command());
    }

    public function testRegistersAFailureWhenTheCurrentPasswordIsWrong(): void
    {
        $this->changePasswordService->method('changePassword')->willThrowException(new InvalidUserPassword());
        $this->attemptLimiter->expects($this->once())->method('registerFailure')->with('user-id');
        $this->attemptLimiter->expects($this->never())->method('reset');
        $this->refreshTokenRepository->expects($this->never())->method('save');

        $this->expectException(InvalidUserPassword::class);

        ($this->handler)($this->command());
    }

    public function testDoesNotTouchTheServiceWhenLocked(): void
    {
        $this->attemptLimiter->method('assertAllowed')->willThrowException(new ChangePasswordRateLimited(120));
        $this->changePasswordService->expects($this->never())->method('changePassword');
        $this->attemptLimiter->expects($this->never())->method('registerFailure');

        $this->expectException(ChangePasswordRateLimited::class);

        ($this->handler)($this->command());
    }

    /**
     * Mistakes unrelated to the current password do not count as a failed attempt.
     */
    public function testOtherDomainErrorsPropagateWithoutRegisteringAFailure(): void
    {
        foreach ([new PasswordUnchanged(), new InvalidNewPassword(8, 44), new NotFoundUser('user-id')] as $error) {
            $limiter = $this->createMock(ChangePasswordAttemptLimiter::class);
            $limiter->expects($this->never())->method('registerFailure');
            $service = $this->createMock(ChangePasswordService::class);
            $service->method('changePassword')->willThrowException($error);

            $handler = new ChangePasswordHandler(
                $service,
                $limiter,
                $this->refreshTokenRepository,
                $this->eventBus,
                $this->logger
            );

            try {
                $handler($this->command());
                $this->fail('Expected ' . $error::class);
            } catch (\Throwable $e) {
                $this->assertSame($error, $e);
            }
        }
    }

    public function testAnEventPublishingFailureDoesNotUndoTheChange(): void
    {
        $user = $this->createMock(User::class);
        $this->changePasswordService->method('changePassword')->willReturn($user);
        $this->refreshTokenRepository->method('findActiveByUserId')->willReturn([]);
        $this->eventBus->method('publish')->willThrowException(new \RuntimeException('mail down'));
        $this->logger->expects($this->once())->method('error');

        ($this->handler)($this->command());
    }
}
