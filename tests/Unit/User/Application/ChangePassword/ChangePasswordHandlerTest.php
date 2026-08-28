<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\ChangePassword;

use App\User\Application\ChangePassword\ChangePasswordHandler;
use App\User\Application\ChangePassword\ChangePasswordCommand;
use App\User\Domain\Exception\ErrorUserChangePassword;
use App\User\Domain\Service\ChangePasswordService;
use PHPUnit\Framework\TestCase;

class ChangePasswordHandlerTest extends TestCase
{
    private ChangePasswordService $changePasswordService;
    private ChangePasswordHandler $handler;

    protected function setUp(): void
    {
        $this->changePasswordService = $this->createMock(ChangePasswordService::class);
        $this->handler = new ChangePasswordHandler($this->changePasswordService);
    }

    public function testHandleChangePasswordSuccess(): void
    {
        $oldPassword = 'old-password';
        $newPassword = 'new-password';

        $command = new ChangePasswordCommand(
            userId: 'user-id',
            oldPassword: $oldPassword,
            newPassword: $newPassword);
        $this->changePasswordService
            ->expects($this->once())
            ->method('changePassword')
            ->with('user-id', $newPassword, $oldPassword);

        ($this->handler)($command);
    }

    public function testHandleChangePasswordThrowsDomainException(): void
    {
        $oldPassword = 'old-password';
        $newPassword = 'new-password';

        $command = new ChangePasswordCommand(
            userId: 'user-id',
            oldPassword: $oldPassword,
            newPassword: $newPassword);
        $this->changePasswordService
            ->expects($this->once())
            ->method('changePassword')
            ->willThrowException(new \Exception('error'));

        $this->expectException(ErrorUserChangePassword::class);
        ($this->handler)($command);
    }
}
