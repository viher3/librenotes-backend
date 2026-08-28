<?php

namespace App\Tests\Unit\User\Application\ActivateAccount;

use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\ContactData\EmailValueObject;
use App\User\Application\ActivateAccount\ActivateAccountCommand;
use App\User\Application\ActivateAccount\ActivateAccountHandler;
use App\User\Domain\Exception\ActivationTokenExpired;
use App\User\Domain\Exception\InvalidActivationToken;
use App\User\Domain\Model\User;
use App\User\Domain\Specification\UniqueEmailSpecificationInterface;
use App\User\Domain\UserRepository;
use App\User\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class ActivateAccountHandlerTest extends TestCase
{
    private UserRepository $userRepository;
    private DateTimeProvider $dateTimeProvider;
    private ActivateAccountHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->dateTimeProvider = $this->createMock(DateTimeProvider::class);
        $this->handler = new ActivateAccountHandler($this->userRepository, $this->dateTimeProvider);
    }

    private function buildUser(\DateTimeImmutable $now): array
    {
        $user = new User(
            uniqueEmailSpecification: $this->createMock(UniqueEmailSpecificationInterface::class),
            id: UserId::random(),
            email: new EmailValueObject('test@example.com'),
            createdAt: $now,
            active: false
        );
        $token = $user->requestEmailActivation($now);

        return [$user, $token];
    }

    public function testActivatesUserWithAValidToken(): void
    {
        $now = new \DateTimeImmutable('2026-01-01 10:00:00');
        [$user, $token] = $this->buildUser($now);

        $this->dateTimeProvider->method('now')->willReturn($now->modify('+1 hour'));
        $this->userRepository->method('findByActivationToken')->with($token)->willReturn($user);
        $this->userRepository->expects($this->once())->method('save');

        $this->handler->__invoke(new ActivateAccountCommand($token));

        $this->assertTrue($user->isActive());
        $this->assertTrue($user->isEmailValidated());
    }

    public function testThrowsWhenTokenIsUnknown(): void
    {
        $this->userRepository->method('findByActivationToken')->willReturn(null);
        $this->dateTimeProvider->method('now')->willReturn(new \DateTimeImmutable());

        $this->expectException(InvalidActivationToken::class);

        $this->handler->__invoke(new ActivateAccountCommand('unknown-token'));
    }

    public function testThrowsWhenTokenHasExpired(): void
    {
        $now = new \DateTimeImmutable('2026-01-01 10:00:00');
        [$user, $token] = $this->buildUser($now);

        $this->dateTimeProvider->method('now')->willReturn(
            $now->modify('+' . (User::MAX_ACTIVATION_TOKEN_VALID_TIME_IN_HOURS + 1) . ' hours')
        );
        $this->userRepository->method('findByActivationToken')->with($token)->willReturn($user);
        $this->userRepository->expects($this->never())->method('save');

        $this->expectException(ActivationTokenExpired::class);

        $this->handler->__invoke(new ActivateAccountCommand($token));
    }
}
