<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventHandler;

use App\Core\Domain\ValueObject\ContactData\EmailValueObject;
use App\User\Domain\Event\UserPasswordChanged;
use App\User\Domain\Service\UserNotifier;
use App\User\Infrastructure\EventHandler\SymfonyUserPasswordChangedEventHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SymfonyUserPasswordChangedEventHandlerTest extends TestCase
{
    private function event(): UserPasswordChanged
    {
        return new UserPasswordChanged('user-id', 'user@example.com', 'es', 'event-id', '2026-01-01 10:00:00');
    }

    public function testSendsThePasswordChangedEmail(): void
    {
        $notifier = $this->createMock(UserNotifier::class);
        $notifier->expects($this->once())
            ->method('sendPasswordChangedEmail')
            ->with($this->callback(static fn (EmailValueObject $e): bool => $e->value() === 'user@example.com'), 'es');

        (new SymfonyUserPasswordChangedEventHandler($notifier, $this->createMock(LoggerInterface::class), true))($this->event());
    }

    public function testDoesNothingWhenEmailsAreDisabled(): void
    {
        $notifier = $this->createMock(UserNotifier::class);
        $notifier->expects($this->never())->method('sendPasswordChangedEmail');

        (new SymfonyUserPasswordChangedEventHandler($notifier, $this->createMock(LoggerInterface::class), false))($this->event());
    }

    public function testANotifierFailureIsLoggedNotPropagated(): void
    {
        $notifier = $this->createMock(UserNotifier::class);
        $notifier->method('sendPasswordChangedEmail')->willThrowException(new \RuntimeException('smtp down'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        (new SymfonyUserPasswordChangedEventHandler($notifier, $logger, true))($this->event());
    }
}
