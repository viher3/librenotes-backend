<?php

namespace App\Tests\Unit\User\Application\Create;

use App\Core\Domain\Bus\Event\EventBus;
use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\Uuid;
use App\Security\Domain\Service\PasswordHasher;
use App\User\Application\Create\CreateUserCommand;
use App\User\Application\Create\CreateUserHandler;
use App\User\Domain\Specification\UniqueEmailSpecificationInterface;
use App\User\Domain\UserRepository;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class CreateUserHandlerTest extends TestCase
{
    private UserRepository $userRepository;
    private PasswordHasher $passwordHasher;
    private EventBus $eventBus;
    private UniqueEmailSpecificationInterface $uniqueEmailSpecification;
    private DateTimeProvider $dateTimeProvider;
    private CreateUserHandler $handler;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(PasswordHasher::class);
        $this->eventBus = $this->createMock(EventBus::class);
        $this->uniqueEmailSpecification = $this->createMock(UniqueEmailSpecificationInterface::class);
        $this->dateTimeProvider = $this->createMock(DateTimeProvider::class);

        $this->handler = new CreateUserHandler(
            $this->userRepository,
            $this->passwordHasher,
            $this->eventBus,
            $this->uniqueEmailSpecification,
            $this->dateTimeProvider
        );
    }

    public function testHandleCreateUserCommand(): void
    {
        $command = new CreateUserCommand(
            id: Uuid::random()->value(),
            email: 'test@example.com',
            password: 'securepassword',
            username: 'testuser',
            isActive: true,
            isEmailValidated: true
        );

        $this->dateTimeProvider
            ->method('now')
            ->willReturn(new \DateTimeImmutable('2023-01-01'));

        $this->passwordHasher
            ->method('hash')
            ->willReturn('hashed-password-value');

        $this->userRepository
            ->expects($this->once())
            ->method('save');

        $this->eventBus
            ->expects($this->once())
            ->method('publish');

        $this->handler->__invoke($command);
    }
}