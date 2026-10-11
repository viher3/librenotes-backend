<?php

namespace App\Tests\Unit\User\Application\SignUp;

use App\Core\Domain\Bus\Event\EventBus;
use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\ContactData\EmailValueObject;
use App\Core\Domain\ValueObject\Uuid;
use App\Security\Domain\Service\PasswordHasher;
use App\User\Application\SignUp\SignUpCommand;
use App\User\Application\SignUp\SignUpHandler;
use App\User\Domain\Exception\UserRegistrationDisabled;
use App\User\Domain\Model\User;
use App\User\Domain\Service\UserNotifier;
use App\User\Domain\Specification\UniqueEmailSpecificationInterface;
use App\User\Domain\UserRepository;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class SignUpHandlerTest extends TestCase
{
    private UserRepository $userRepository;
    private PasswordHasher $passwordHasher;
    private EventBus $eventBus;
    private UniqueEmailSpecificationInterface $uniqueEmailSpecification;
    private DateTimeProvider $dateTimeProvider;
    private UserNotifier $userNotifier;

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
        $this->userNotifier = $this->createMock(UserNotifier::class);

        $this->dateTimeProvider->method('now')->willReturn(new \DateTimeImmutable('2026-01-01'));
        $this->passwordHasher->method('hash')->willReturn('hashed-password-value');
    }

    private function handler(bool $sendActivationEmail, bool $registrationEnabled = true): SignUpHandler
    {
        return new SignUpHandler(
            $this->userRepository,
            $this->passwordHasher,
            $this->eventBus,
            $this->uniqueEmailSpecification,
            $this->dateTimeProvider,
            $this->userNotifier,
            $sendActivationEmail,
            $registrationEnabled
        );
    }

    public function testSignUpCreatesAnInactiveUserAndSendsActivationEmail(): void
    {
        $command = new SignUpCommand(
            id: Uuid::random()->value(),
            email: 'test@example.com',
            password: 'securepassword',
            username: 'testuser'
        );

        $savedUser = null;
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (User $user) use (&$savedUser) {
                $savedUser = $user;
            });

        $this->eventBus->expects($this->once())->method('publish');

        $this->userNotifier
            ->expects($this->once())
            ->method('sendActivationEmail')
            ->with(
                $this->callback(fn (EmailValueObject $email) => $email->value() === 'test@example.com'),
                $this->isType('string')
            );

        $this->handler(true)->__invoke($command);

        $this->assertFalse($savedUser->isActive());
        $this->assertFalse($savedUser->isEmailValidated());
    }

    public function testSignUpDoesNotSendActivationEmailWhenDisabled(): void
    {
        $command = new SignUpCommand(
            id: Uuid::random()->value(),
            email: 'test2@example.com',
            password: 'securepassword'
        );

        $this->userRepository->expects($this->once())->method('save');
        $this->eventBus->expects($this->once())->method('publish');
        $this->userNotifier->expects($this->never())->method('sendActivationEmail');

        $this->handler(false)->__invoke($command);
    }

    public function testSignUpIsRejectedWhenRegistrationIsDisabled(): void
    {
        $command = new SignUpCommand(
            id: Uuid::random()->value(),
            email: 'test@example.com',
            password: 'securepassword'
        );

        $this->userRepository->expects($this->never())->method('save');
        $this->eventBus->expects($this->never())->method('publish');

        $this->expectException(UserRegistrationDisabled::class);

        $this->handler(true, false)->__invoke($command);
    }
}
