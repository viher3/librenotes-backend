<?php

namespace App\Tests\Unit\User\Application\Detail;

use App\Core\Domain\ValueObject\ContactData\EmailValueObject;
use App\User\Application\Detail\DetailUserHandler;
use App\User\Application\Detail\DetailUserQuery;
use App\User\Application\Detail\DetailUserResponse;
use App\User\Domain\Exception\NotFoundUser;
use App\User\Domain\Model\User;
use App\User\Domain\Specification\UniqueEmailSpecificationInterface;
use App\User\Domain\UserRepository;
use App\User\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class DetailUserHandlerTest extends TestCase
{
    private UserRepository $userRepository;
    private DetailUserHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new DetailUserHandler($this->userRepository);
    }

    private function mockUser(): User
    {
        $uniqueEmailSpecification = $this->createMock(UniqueEmailSpecificationInterface::class);
        $uniqueEmailSpecification->method('isUnique')->willReturn(true);

        return new User(
            $uniqueEmailSpecification,
            UserId::random(),
            new EmailValueObject('test@example.com'),
            new \DateTimeImmutable('2023-01-01 00:00:00'),
            true,
            'testuser'
        );
    }

    public function testHandleReturnsDetailUserResponse(): void
    {
        $userId = '123';
        $user = $this->mockUser();

        $this->userRepository
            ->method('getById')
            ->with($userId)
            ->willReturn($user);

        $query = new DetailUserQuery($userId);
        $response = ($this->handler)($query);

        $this->assertInstanceOf(DetailUserResponse::class, $response);
        $this->assertSame($user, $response->user);
        $this->assertIsArray($response->getResult());
    }

    public function testHandleThrowsNotFoundUserException(): void
    {
        $userId = '123';
        $this->userRepository
            ->method('getById')
            ->with($userId)
            ->willReturn(null);

        $query = new DetailUserQuery($userId);

        $this->expectException(NotFoundUser::class);
        $this->expectExceptionMessage($userId);

        ($this->handler)($query);
    }
}