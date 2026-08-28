<?php

namespace App\Tests\Unit\User\Application\Update;

use App\User\Application\Update\UpdateUserCommand;
use App\User\Application\Update\UpdateUserHandler;
use App\User\Domain\Exception\NotFoundUser;
use App\User\Domain\Model\User;
use App\User\Domain\Specification\UniqueEmailSpecificationInterface;
use App\User\Domain\UserRepository;
use PHPUnit\Framework\TestCase;

class UpdateUserHandlerTest extends TestCase
{
    private UserRepository $userRepository;
    private UniqueEmailSpecificationInterface $uniqueEmailSpecification;
    private UpdateUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->uniqueEmailSpecification = $this->createMock(UniqueEmailSpecificationInterface::class);

        $this->handler = new UpdateUserHandler(
            $this->userRepository,
            $this->uniqueEmailSpecification,
        );
    }

    public function testHandleUpdatesUserSuccessfully(): void
    {
        $userId = '123';
        $fields = ['email' => 'newemail@example.com'];
        $command = new UpdateUserCommand($userId, $fields);

        $user = $this->createMock(User::class);
        $this->userRepository
            ->method('getById')
            ->with($userId)
            ->willReturn($user);

        $user->expects($this->once())
            ->method('update')
            ->with(
                $fields,
                $this->uniqueEmailSpecification
            );

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->handler->__invoke($command);
    }

    public function testHandleThrowsNotFoundUserException(): void
    {
        $userId = '123';
        $fields = ['email' => 'newemail@example.com'];
        $command = new UpdateUserCommand($userId, $fields);

        $this->userRepository
            ->method('getById')
            ->with($userId)
            ->willReturn(null);

        $this->expectException(NotFoundUser::class);

        $this->handler->__invoke($command);
    }
}