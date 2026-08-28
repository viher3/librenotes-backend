<?php

namespace App\Tests\Unit\User\Application\Delete;

use App\User\Application\Delete\DeleteUserCommand;
use App\User\Application\Delete\DeleteUserHandler;
use App\User\Domain\Exception\NotFoundUser;
use App\User\Domain\Model\User;
use App\User\Domain\UserRepository;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class DeleteUserHandlerTest extends TestCase
{
    private UserRepository $userRepository;
    private DeleteUserHandler $handler;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new DeleteUserHandler($this->userRepository);
    }

    public function testInvokeDeletesUserSuccessfully(): void
    {
        $userId = '123';
        $user = $this->createMock(User::class);

        $this->userRepository
            ->expects($this->once())
            ->method('getById')
            ->with($userId)
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('delete')
            ->with($user);

        $command = new DeleteUserCommand($userId);
        ($this->handler)($command);
    }

    public function testInvokeThrowsNotFoundUserException(): void
    {
        $userId = '123';

        $this->userRepository
            ->expects($this->once())
            ->method('getById')
            ->with($userId)
            ->willReturn(null);

        $this->expectException(NotFoundUser::class);
        $this->expectExceptionMessage($userId);

        $command = new DeleteUserCommand($userId);
        ($this->handler)($command);
    }
}