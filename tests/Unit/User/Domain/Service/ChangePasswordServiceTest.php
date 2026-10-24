<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Service;

use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\ContactData\EmailValueObject;
use App\Security\Domain\Service\PasswordHasher;
use App\User\Domain\Event\UserPasswordChanged;
use App\User\Domain\Exception\InvalidNewPassword;
use App\User\Domain\Exception\InvalidUserPassword;
use App\User\Domain\Exception\NotFoundUser;
use App\User\Domain\Exception\PasswordUnchanged;
use App\User\Domain\Model\User;
use App\User\Domain\Service\ChangePasswordService;
use App\User\Domain\Specification\UniqueEmailSpecificationInterface;
use App\User\Domain\UserRepository;
use App\User\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class ChangePasswordServiceTest extends TestCase
{
    private const string USER_ID = '11111111-1111-1111-1111-111111111111';
    private const string CURRENT = 'current-password';

    private UserRepository $userRepository;
    private PasswordHasher $passwordHasher;
    private ChangePasswordService $service;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(PasswordHasher::class);
        $dateTimeProvider = $this->createMock(DateTimeProvider::class);
        $dateTimeProvider->method('now')->willReturn(new \DateTimeImmutable('2026-01-01 10:00:00'));

        $this->service = new ChangePasswordService($this->userRepository, $this->passwordHasher, $dateTimeProvider);
    }

    private function user(): User
    {
        $user = new User(
            $this->createMock(UniqueEmailSpecificationInterface::class),
            new UserId(self::USER_ID),
            new EmailValueObject('user@example.com'),
            new \DateTimeImmutable('2025-01-01'),
        );
        $user->pullDomainEvents();

        return $user;
    }

    private function givenCurrentPasswordIs(string $password): void
    {
        $this->passwordHasher->method('verify')
            ->willReturnCallback(static fn ($user, string $given): bool => $given === $password);
    }

    public function testChangesThePasswordRevokesTokensAndRecordsTheEvent(): void
    {
        $user = $this->user();
        $this->userRepository->method('getById')->with(self::USER_ID)->willReturn($user);
        $this->givenCurrentPasswordIs(self::CURRENT);
        $this->passwordHasher->method('hash')->willReturn('new-hash');
        $versionBefore = $user->getTokenVersion()->value();

        $this->userRepository->expects($this->once())->method('save')->with($user);

        $this->service->changePassword(self::USER_ID, 'brand-new-password', self::CURRENT);

        $this->assertSame('new-hash', $user->getPassword());
        $this->assertSame($versionBefore + 1, $user->getTokenVersion()->value());

        $events = $user->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserPasswordChanged::class, $events[0]);
        $this->assertSame('user@example.com', $events[0]->email());
    }

    public function testRejectsAnEmptyCurrentPasswordInsteadOfSkippingTheCheck(): void
    {
        $this->userRepository->method('getById')->willReturn($this->user());
        $this->givenCurrentPasswordIs(self::CURRENT);
        $this->userRepository->expects($this->never())->method('save');

        $this->expectException(InvalidUserPassword::class);

        $this->service->changePassword(self::USER_ID, 'brand-new-password', '');
    }

    public function testRejectsAWrongCurrentPassword(): void
    {
        $this->userRepository->method('getById')->willReturn($this->user());
        $this->givenCurrentPasswordIs(self::CURRENT);
        $this->userRepository->expects($this->never())->method('save');

        $this->expectException(InvalidUserPassword::class);

        $this->service->changePassword(self::USER_ID, 'brand-new-password', 'wrong-password');
    }

    public function testRejectsANewPasswordOutsideTheAllowedLength(): void
    {
        $this->userRepository->method('getById')->willReturn($this->user());
        $this->givenCurrentPasswordIs(self::CURRENT);

        $this->expectException(InvalidNewPassword::class);

        $this->service->changePassword(self::USER_ID, 'short', self::CURRENT);
    }

    public function testRejectsTheSamePassword(): void
    {
        $this->userRepository->method('getById')->willReturn($this->user());
        $this->givenCurrentPasswordIs(self::CURRENT);

        $this->expectException(PasswordUnchanged::class);

        $this->service->changePassword(self::USER_ID, self::CURRENT, self::CURRENT);
    }

    public function testThrowsWhenTheUserDoesNotExist(): void
    {
        $this->userRepository->method('getById')->willReturn(null);

        $this->expectException(NotFoundUser::class);

        $this->service->changePassword(self::USER_ID, 'brand-new-password', self::CURRENT);
    }
}
