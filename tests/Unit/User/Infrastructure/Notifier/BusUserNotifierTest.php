<?php

namespace App\Tests\Unit\User\Infrastructure\Notifier;

use App\Core\Domain\ValueObject\ContactData\EmailValueObject;
use App\Core\Infrastructure\Bus\Command\SymfonyTransactionalCommandBus;
use App\Notification\Application\Send\SendNotificationCommand;
use App\User\Infrastructure\Notifier\BusUserNotifier;
use PHPUnit\Framework\TestCase;

class BusUserNotifierTest extends TestCase
{
    public function testSendsAnEmailNotificationWithTheActivationLink(): void
    {
        $commandBus = $this->createMock(SymfonyTransactionalCommandBus::class);
        $notifier = new BusUserNotifier($commandBus, 'https://app.example.com/');

        $commandBus
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function (SendNotificationCommand $command) {
                return $command->channel === 'email'
                    && $command->templateKey === 'user.activation'
                    && $command->recipient === 'test@example.com'
                    && $command->payload['activation_link'] === 'https://app.example.com/activate-account?token=abc123';
            }));

        $notifier->sendActivationEmail(new EmailValueObject('test@example.com'), 'abc123');
    }

    public function testSendsAPasswordChangedEmailInTheUsersLocale(): void
    {
        $commandBus = $this->createMock(SymfonyTransactionalCommandBus::class);
        $notifier = new BusUserNotifier($commandBus, 'https://app.example.com/');

        $commandBus
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function (SendNotificationCommand $command) {
                return $command->channel === 'email'
                    && $command->templateKey === 'user.password_changed'
                    && $command->recipient === 'test@example.com'
                    && $command->locale === 'es'
                    && $command->payload === [];
            }));

        $notifier->sendPasswordChangedEmail(new EmailValueObject('test@example.com'), 'es');
    }
}
